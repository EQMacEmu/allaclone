# Offline Lua quest ingest for AllaClone (Windows).
# Applies includes/sql/quests.sql and fills quest_* tables from EQEmu *.lua scripts.
# Runtime PHP pages only SELECT these tables — they never call this script.
#
# Usage:
#   .\import_lua_quests.ps1 -QuestRoot 'C:\TAKP\quests'
#   .\import_lua_quests.ps1 -QuestRoot 'C:\TAKP\quests' -DbHost localhost -DbUser root -DbPass root -DbName peq
#   .\import_lua_quests.ps1 -QuestRoot 'C:\TAKP\quests' -Zones qeynos,freporte
#
# Heuristic limits: regex extraction; may miss concatenated strings / variable ids.

param(
    [Parameter(Mandatory = $true)][string]$QuestRoot,
    [string]$DbHost = "localhost",
    [string]$DbUser = "root",
    [string]$DbPass = "root",
    [string]$DbName = "peq",
    [string]$MysqlExe = "",
    [string[]]$Zones = @(),
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"
$QuestRoot = $QuestRoot.TrimEnd('\', '/')
if (-not (Test-Path -LiteralPath $QuestRoot)) {
    throw "Quest root not found: $QuestRoot"
}

if (-not $MysqlExe) {
    $candidates = @(
        "mysql",
        "C:\Program Files\MySQL\MySQL Server 9.5\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
        "C:\xampp\mysql\bin\mysql.exe"
    )
    foreach ($c in $candidates) {
        if ($c -eq "mysql") {
            $cmd = Get-Command mysql -ErrorAction SilentlyContinue
            if ($cmd) { $MysqlExe = $cmd.Source; break }
        } elseif (Test-Path -LiteralPath $c) {
            $MysqlExe = $c; break
        }
    }
}
if (-not $MysqlExe) { throw "mysql client not found. Pass -MysqlExe." }

function Invoke-Mysql {
    param([string]$Sql)
    $argList = @("-h", $DbHost, "-u", $DbUser, $DbName, "--batch", "--raw", "--default-character-set=latin1")
    if ($DbPass -ne "") {
        $argList = @("-h", $DbHost, "-u", $DbUser, "-p$DbPass", $DbName, "--batch", "--raw", "--default-character-set=latin1")
    }
    # Pipe SQL via stdin — Windows CreateProcess argv length breaks on large -e payloads.
    $prevEap = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $tmp = [IO.Path]::GetTempFileName()
    try {
        [IO.File]::WriteAllText($tmp, $Sql + "`n", [Text.UTF8Encoding]::new($false))
        $out = Get-Content -LiteralPath $tmp -Raw | & $MysqlExe @argList 2>&1
        $code = $LASTEXITCODE
    } finally {
        Remove-Item -LiteralPath $tmp -Force -ErrorAction SilentlyContinue
        $ErrorActionPreference = $prevEap
    }
    $text = @($out | ForEach-Object { "$_" }) -join "`n"
    if ($code -ne 0) {
        $preview = if ($Sql.Length -gt 400) { $Sql.Substring(0, 400) + "..." } else { $Sql }
        throw "mysql failed (exit $code): $text`nSQL: $preview"
    }
    return @($out | Where-Object {
            $s = "$_"
            $s -notmatch '^\s*mysql:\s*\[Warning\]' -and $s -notmatch 'Using a password on the command line'
        })
}

function Invoke-MysqlQuery {
    param([string]$Sql)
    $raw = Invoke-Mysql -Sql $Sql
    $lines = @($raw | Where-Object { $_ -is [string] -or $_ -is [System.Management.Automation.ErrorRecord] } | ForEach-Object { "$_" })
    # mysql prints warnings to stderr; filter real rows
    $rows = @()
    $header = $null
    foreach ($line in $lines) {
        if ($line -match '^(mysql:|Warning:|Note:)') { continue }
        if ($null -eq $header) { $header = $line -split "`t"; continue }
        $cols = $line -split "`t"
        $obj = [ordered]@{}
        for ($i = 0; $i -lt $header.Count; $i++) {
            $obj[$header[$i]] = if ($i -lt $cols.Count) { $cols[$i] } else { "" }
        }
        $rows += [pscustomobject]$obj
    }
    return $rows
}

function Escape-Sql([string]$s) {
    if ($null -eq $s) { return "" }
    return $s.Replace("\", "\\").Replace("'", "''")
}

function Normalize-NpcKey([string]$Name) {
    if ([string]::IsNullOrWhiteSpace($Name)) { return "" }
    $s = $Name.Replace('_', ' ').ToLowerInvariant()
    $s = [regex]::Replace($s, '^[#~!]+', '')
    $s = [regex]::Replace($s.Trim(), '\s+', ' ')
    return $s
}

function Standing-Label([int]$minRaw) {
    if ($minRaw -ge 1100) { return "Ally+" }
    if ($minRaw -ge 750) { return "Warmly+" }
    if ($minRaw -ge 500) { return "Kindly+" }
    if ($minRaw -ge 100) { return "Amiably+" }
    if ($minRaw -ge 0) { return "Indifferent+" }
    if ($minRaw -ge -100) { return "Apprehensive+" }
    if ($minRaw -ge -500) { return "Dubious+" }
    if ($minRaw -ge -750) { return "Threateningly+" }
    return "Ready to attack+"
}

function Parse-QuestReward([string]$Line) {
    if ($Line -notmatch '(?i)e\.other:QuestReward') { return $null }
    $out = [ordered]@{
        reward_item_ids = New-Object System.Collections.ArrayList
        reward_exp = 0; reward_faction_flag = 0
        copper = 0; silver = 0; gold = 0; platinum = 0
        faction_hits = New-Object System.Collections.ArrayList
    }
    $itemsM = [regex]::Match($Line, '(?i)items\s*=\s*\{\s*([\d,\s]+)\}')
    if ($itemsM.Success) {
        foreach ($n in [regex]::Matches($itemsM.Groups[1].Value, '\d+')) { [void]$out.reward_item_ids.Add([int]$n.Value) }
        $expM = [regex]::Match($Line, '(?i)\bexp\s*=\s*(\d+)')
        if ($expM.Success) { $out.reward_exp = [int]$expM.Groups[1].Value }
        foreach ($coin in @('copper','silver','gold','platinum')) {
            $cm = [regex]::Match($Line, "(?i)\b$coin\s*=\s*(\d+)")
            if ($cm.Success) { $out[$coin] = [int]$cm.Groups[1].Value }
        }
        $fm = [regex]::Match($Line, '(?i)\bfactionid\s*=\s*(\d+)')
        $dm = [regex]::Match($Line, '(?i)\bfactionmod\s*=\s*(-?\d+)')
        if ($fm.Success -and $dm.Success) {
            [void]$out.faction_hits.Add([ordered]@{ faction_id = [int]$fm.Groups[1].Value; delta = [int]$dm.Groups[1].Value })
        }
        if ($Line -match '(?i)\bfaction\s*=\s*true\b') { $out.reward_faction_flag = 1 }
        return $out
    }
    $mn = [regex]::Match($Line, '(?i)e\.other:QuestReward\s*\(\s*e\.self\s*,\s*([\d,\s]+)\)')
    if (-not $mn.Success) { return $null }
    $nums = foreach ($x in [regex]::Matches($mn.Groups[1].Value, '\d+')) { [int]$x.Value }
    $arr = @($nums)
    if ($arr.Count -lt 5) { return $null }
    $out.copper = $arr[0]; $out.silver = $arr[1]; $out.gold = $arr[2]; $out.platinum = $arr[3]
    if ($arr[4] -gt 0) { [void]$out.reward_item_ids.Add([int]$arr[4]) }
    if ($arr.Count -ge 6) { $out.reward_exp = $arr[5] }
    if ($arr.Count -ge 7 -and $arr[6]) { $out.reward_faction_flag = 1 }
    return $out
}

function Parse-LuaFile([string]$Path, [string]$Rel) {
    $raw = [IO.File]::ReadAllText($Path)
    $raw = $raw -replace "`r`n", "`n" -replace "`r", "`n"
    $lines = $raw -split "`n"
    $result = [ordered]@{
        trades = New-Object System.Collections.ArrayList
        faction_hits = New-Object System.Collections.ArrayList
        faction_reqs = New-Object System.Collections.ArrayList
        say_branches = New-Object System.Collections.ArrayList
        literal_npc_ids = New-Object System.Collections.ArrayList
    }
    foreach ($rx in @(
        '(?i)\beq\.spawn2\s*\(\s*(\d+)',
        '(?i)\beq\.unique_spawn\s*\(\s*(\d+)',
        '(?i)\beq\.depop_all\s*\(\s*(\d+)',
        '(?i)\beq\.signal\s*\(\s*(\d+)'
    )) {
        foreach ($m in [regex]::Matches($raw, $rx)) {
            $id = [int]$m.Groups[1].Value
            if (-not $result.literal_npc_ids.Contains($id)) { [void]$result.literal_npc_ids.Add($id) }
        }
    }
    $n = $lines.Count
    $rxTurn = [regex]'(?i)item_lib\.check_turn_in\s*\(\s*e\.self\s*,\s*e\.trade\s*,\s*\{([^}]+)\}'
    for ($i = 0; $i -lt $n; $i++) {
        $m = $rxTurn.Match($lines[$i])
        if (-not $m.Success) { continue }
        $giveIds = New-Object System.Collections.ArrayList
        foreach ($im in [regex]::Matches($m.Groups[1].Value, '(?i)item\s*\d+\s*=\s*(\d+)')) {
            [void]$giveIds.Add([int]$im.Groups[1].Value)
        }
        if ($giveIds.Count -eq 0) { continue }
        $reward = [ordered]@{
            reward_item_ids = New-Object System.Collections.ArrayList
            reward_exp = 0; reward_faction_flag = 0
            copper = 0; silver = 0; gold = 0; platinum = 0
            faction_hits = New-Object System.Collections.ArrayList
        }
        $localHits = New-Object System.Collections.ArrayList
        $lim = [Math]::Min($i + 28, $n - 1)
        for ($j = $i; $j -le $lim; $j++) {
            $qr = Parse-QuestReward $lines[$j]
            if ($null -ne $qr) { $reward = $qr; break }
        }
        for ($j = $i; $j -le $lim; $j++) {
            foreach ($fm in [regex]::Matches($lines[$j], '(?i)e\.other:Faction\s*\(\s*e\.self\s*,\s*(\d+)\s*,\s*(-?\d+)')) {
                [void]$localHits.Add([ordered]@{ faction_id = [int]$fm.Groups[1].Value; delta = [int]$fm.Groups[2].Value; line_no = $j + 1; script_file = $Rel })
            }
        }
        foreach ($fh in @($reward.faction_hits)) {
            [void]$localHits.Add([ordered]@{ faction_id = [int]$fh.faction_id; delta = [int]$fh.delta; line_no = $i + 1; script_file = $Rel })
        }
        [void]$result.trades.Add([ordered]@{
                line_no = $i + 1; script_file = $Rel; give_ids = @($giveIds.ToArray())
                reward = $reward; faction_hits = @($localHits.ToArray())
            })
    }
    for ($i = 0; $i -lt $n; $i++) {
        foreach ($fm in [regex]::Matches($lines[$i], '(?i)e\.other:Faction\s*\(\s*e\.self\s*,\s*(\d+)\s*,\s*(-?\d+)')) {
            [void]$result.faction_hits.Add([ordered]@{ faction_id = [int]$fm.Groups[1].Value; delta = [int]$fm.Groups[2].Value; line_no = $i + 1; script_file = $Rel })
        }
        foreach ($m in [regex]::Matches($lines[$i], '(?i)GetFactionValue\s*\(\s*e\.self\s*\)\s*(>=|>)\s*(-?\d+)')) {
            $min = [int]$m.Groups[2].Value
            if ($m.Groups[1].Value -eq '>') { $min++ }
            [void]$result.faction_reqs.Add([ordered]@{
                    faction_id = 0; min_raw = $min; standing_label = (Standing-Label $min)
                    line_no = $i + 1; script_file = $Rel
                })
        }
    }
    $inSay = $false
    for ($i = 0; $i -lt $n; $i++) {
        if ($lines[$i] -match '(?i)^\s*function\s+event_say\b') { $inSay = $true; continue }
        if ($inSay -and $lines[$i] -match '(?i)^\s*function\s+') { $inSay = $false }
        if (-not $inSay) { continue }
        $bm = [regex]::Match($lines[$i], '(?i)^(?<ws>\s*)(?:if|elseif)\b.*?:findi\s*\(\s*"(?<kw>[^"]{1,64})"\s*\)')
        if (-not $bm.Success) { continue }
        $branchLen = $bm.Groups['ws'].Value.Length
        $keyword = $bm.Groups['kw'].Value
        $respKind = ""; $respText = ""; $respLine = 0
        $lim = [Math]::Min($i + 60, $n - 1)
        for ($j = $i + 1; $j -le $lim; $j++) {
            $jl = $lines[$j]
            if ([string]::IsNullOrWhiteSpace($jl)) { continue }
            $lj = $jl.Length - $jl.TrimStart().Length
            $trim = $jl.TrimStart()
            if ($lj -le $branchLen -and $trim -match '^(elseif|else)\b') { break }
            if ($respText.Length -eq 0) {
                $sm = [regex]::Match($jl, '(?i)e\.self:Say\s*\(\s*"([^"]{1,400})"')
                if ($sm.Success) { $respKind = "say"; $respText = $sm.Groups[1].Value; $respLine = $j + 1 }
                else {
                    $em = [regex]::Match($jl, '(?i)e\.self:Emote\s*\(\s*"([^"]{1,400})"')
                    if ($em.Success) { $respKind = "emote"; $respText = $em.Groups[1].Value; $respLine = $j + 1 }
                }
            }
        }
        if ($respText.Length -gt 0) {
            [void]$result.say_branches.Add([ordered]@{
                    keyword = $keyword; response_kind = $respKind; response_text = $respText
                    line_no = $(if ($respLine) { $respLine } else { $i + 1 }); script_file = $Rel
                })
        }
    }
    return $result
}

Write-Host "Loading npc_types / items / faction_list from $DbName..." -ForegroundColor Cyan
$npcRowsDb = Invoke-MysqlQuery "SELECT n.id, n.name, IFNULL(nf.primaryfaction,0) AS primary_faction_id FROM npc_types n LEFT JOIN npc_faction nf ON nf.id=n.npc_faction_id"
$npcMap = @{}
$npcById = @{}
foreach ($r in $npcRowsDb) {
    $meta = [ordered]@{ id = [int]$r.id; name = [string]$r.name; primary_faction_id = [int]$r.primary_faction_id }
    $npcById[[int]$r.id] = $meta
    $k = Normalize-NpcKey ([string]$r.name)
    if ($k -eq "") { continue }
    if (-not $npcMap.ContainsKey($k)) { $npcMap[$k] = New-Object System.Collections.ArrayList }
    [void]$npcMap[$k].Add($meta)
}
$itemSet = @{}
foreach ($r in (Invoke-MysqlQuery "SELECT id FROM items")) { $itemSet[[int]$r.id] = $true }
$factionSet = @{}
foreach ($r in (Invoke-MysqlQuery "SELECT id FROM faction_list")) { $factionSet[[int]$r.id] = $true }
Write-Host ("  npc_keys={0} items={1} factions={2}" -f $npcMap.Count, $itemSet.Count, $factionSet.Count)

$schema = Join-Path (Split-Path $PSScriptRoot -Parent) "includes\sql\quests.sql"
if (-not $DryRun) {
    if (-not (Test-Path -LiteralPath $schema)) { throw "Missing $schema" }
    $sqlText = [IO.File]::ReadAllText($schema)
    # mysql source works better as multi statements via temp file + mysql redirect
    $tmp = [IO.Path]::GetTempFileName() + ".sql"
    [IO.File]::WriteAllText($tmp, $sqlText, [Text.UTF8Encoding]::new($false))
    $argList = @("-h", $DbHost, "-u", $DbUser, $DbName)
    if ($DbPass -ne "") { $argList = @("-h", $DbHost, "-u", $DbUser, "-p$DbPass", $DbName) }
    $prevEap = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    Get-Content -LiteralPath $tmp -Raw | & $MysqlExe @argList 2>&1 | Out-Null
    $code = $LASTEXITCODE
    $ErrorActionPreference = $prevEap
    Remove-Item $tmp -Force -ErrorAction SilentlyContinue
    if ($code -ne 0) { throw "Failed applying schema (exit $code)" }
    Write-Host "Applied includes/sql/quests.sql" -ForegroundColor Green
}

$exclude = @("global","templates","lua_modules","plugins")
$zoneFilter = @{}
foreach ($z in $Zones) { if ($z) { $zoneFilter[$z.ToLowerInvariant()] = $true } }
$zoneDirs = Get-ChildItem -LiteralPath $QuestRoot -Directory | Where-Object {
    $exclude -notcontains $_.Name -and ($zoneFilter.Count -eq 0 -or $zoneFilter.ContainsKey($_.Name.ToLowerInvariant()))
} | Sort-Object Name

$npcRows = @{}
foreach ($zd in $zoneDirs) {
    $zn = $zd.Name
    $files = @(Get-ChildItem -LiteralPath $zd.FullName -Recurse -File -Filter *.lua -ErrorAction SilentlyContinue)
    if ($files.Count -eq 0) { continue }
    foreach ($f in $files) {
        $rel = $f.FullName.Substring($zd.FullName.Length).TrimStart('\','/').Replace('\','/')
        $parsed = Parse-LuaFile -Path $f.FullName -Rel $rel
        $targets = @{}
        $stemKey = Normalize-NpcKey ([IO.Path]::GetFileNameWithoutExtension($f.Name))
        if ($stemKey -ne "" -and $npcMap.ContainsKey($stemKey)) {
            foreach ($cand in $npcMap[$stemKey]) { $targets[[int]$cand.id] = $cand }
        }
        foreach ($lid in @($parsed.literal_npc_ids)) {
            if ($npcById.ContainsKey([int]$lid)) {
                $targets[[int]$lid] = $npcById[[int]$lid]
            }
        }
        if ($targets.Count -eq 0) { continue }
        foreach ($npcId in $targets.Keys) {
            $meta = $targets[$npcId]
            $key = "$zn|$npcId"
            if (-not $npcRows.ContainsKey($key)) {
                $npcRows[$key] = [ordered]@{
                    zone_short = $zn; npc_id = [int]$npcId; npc_name = [string]$meta.name
                    primary_faction_id = [int]$meta.primary_faction_id
                    script_paths = New-Object 'System.Collections.Generic.HashSet[string]'
                    trades = New-Object System.Collections.ArrayList
                    faction_hits = New-Object System.Collections.ArrayList
                    faction_reqs = New-Object System.Collections.ArrayList
                    say_branches = New-Object System.Collections.ArrayList
                }
            }
            [void]$npcRows[$key].script_paths.Add($rel)
            foreach ($t in @($parsed.trades)) { [void]$npcRows[$key].trades.Add($t) }
            foreach ($h in @($parsed.faction_hits)) { [void]$npcRows[$key].faction_hits.Add($h) }
            foreach ($r in @($parsed.faction_reqs)) {
                $rr = [ordered]@{} + $r
                $rr.faction_id = [int]$meta.primary_faction_id
                [void]$npcRows[$key].faction_reqs.Add($rr)
            }
            foreach ($b in @($parsed.say_branches)) { [void]$npcRows[$key].say_branches.Add($b) }
        }
    }
    Write-Host ("  scanned {0} ({1} lua)" -f $zn, $files.Count)
}

if ($DryRun) {
    Write-Host ("Dry run: would write {0} quest NPCs" -f $npcRows.Count) -ForegroundColor Yellow
    exit 0
}

Write-Host ("Writing {0} quest NPCs to SQL dump..." -f $npcRows.Count) -ForegroundColor Cyan
$stats = @{ npcs = 0; trades = 0; says = 0; hits = 0; reqs = 0; skipped_items = 0 }
$itemIndex = @{}
$dumpPath = [IO.Path]::GetTempFileName() + ".sql"
$sw = New-Object System.IO.StreamWriter($dumpPath, $false, [Text.UTF8Encoding]::new($false))
$nextNpcId = 1
$nextTradeId = 1
$nextSayId = 1
$nextReqId = 1
$nextHitId = 1
$nextTiId = 1
try {
    $sw.WriteLine("SET NAMES latin1;")
    foreach ($key in ($npcRows.Keys | Sort-Object)) {
        $row = $npcRows[$key]
        $paths = ($row.script_paths | Sort-Object) -join ','
        if ($paths.Length -gt 500) { $paths = $paths.Substring(0, 497) + '...' }
        $zn = Escape-Sql $row.zone_short
        $nn = Escape-Sql $row.npc_name
        $sp = Escape-Sql $paths
        $npcId = [int]$row.npc_id
        $pf = [int]$row.primary_faction_id
        $questNpcId = $nextNpcId
        $nextNpcId++
        $sw.WriteLine("INSERT INTO quest_npc (id,zone_short,npc_id,npc_name,primary_faction_id,script_paths) VALUES ($questNpcId,'$zn',$npcId,'$nn',$pf,'$sp');")
        $stats.npcs++

        $seenSay = @{}
        foreach ($b in @($row.say_branches)) {
            $sk = ($b.keyword + '|' + $b.response_text).ToLowerInvariant()
            if ($seenSay.ContainsKey($sk)) { continue }
            $seenSay[$sk] = $true
            $kwRaw = [string]$b.keyword
            if ($kwRaw.Length -gt 64) { $kwRaw = $kwRaw.Substring(0, 64) }
            $kw = Escape-Sql $kwRaw
            $rk = Escape-Sql ([string]$b.response_kind)
            $rtFull = [string]$b.response_text
            if ($rtFull.Length -gt 512) { $rtFull = $rtFull.Substring(0, 512) }
            $rt = Escape-Sql $rtFull
            $sf = Escape-Sql ([string]$b.script_file)
            $ln = [int]$b.line_no
            $sid = $nextSayId; $nextSayId++
            $sw.WriteLine("INSERT INTO quest_say_branch (id,quest_npc_id,zone_short,npc_id,keyword,response_kind,response_text,script_file,line_no) VALUES ($sid,$questNpcId,'$zn',$npcId,'$kw','$rk','$rt','$sf',$ln);")
            $stats.says++
        }

        $seenReq = @{}
        foreach ($r in @($row.faction_reqs)) {
            $fid = [int]$r.faction_id
            if ($fid -gt 0 -and -not $factionSet.ContainsKey($fid)) { continue }
            $rk = "${fid}:$($r.min_raw)"
            if ($seenReq.ContainsKey($rk)) { continue }
            $seenReq[$rk] = $true
            $lab = Escape-Sql ([string]$r.standing_label)
            $sf = Escape-Sql ([string]$r.script_file)
            $ln = [int]$r.line_no
            $min = [int]$r.min_raw
            $rid = $nextReqId; $nextReqId++
            $sw.WriteLine("INSERT INTO quest_faction_req (id,quest_npc_id,zone_short,npc_id,faction_id,min_raw,standing_label,script_file,line_no) VALUES ($rid,$questNpcId,'$zn',$npcId,$fid,$min,'$lab','$sf',$ln);")
            $stats.reqs++
        }

        $npcHitKeys = @{}
        foreach ($t in @($row.trades)) {
            $sf = Escape-Sql ([string]$t.script_file)
            $ln = [int]$t.line_no
            $re = [int]$t.reward.reward_exp
            $cu = [int]$t.reward.copper; $si = [int]$t.reward.silver; $go = [int]$t.reward.gold; $pl = [int]$t.reward.platinum
            $rf = [int]$t.reward.reward_faction_flag
            $tid = $nextTradeId; $nextTradeId++
            $sw.WriteLine("INSERT INTO quest_trade (id,quest_npc_id,zone_short,npc_id,script_file,line_no,reward_exp,copper,silver,gold,platinum,reward_faction_flag) VALUES ($tid,$questNpcId,'$zn',$npcId,'$sf',$ln,$re,$cu,$si,$go,$pl,$rf);")
            $stats.trades++
            $ord = 0
            foreach ($iid in @($t.give_ids)) {
                $iid = [int]$iid
                if (-not $itemSet.ContainsKey($iid)) { $stats.skipped_items++; continue }
                $ti = $nextTiId; $nextTiId++
                $sw.WriteLine("INSERT INTO quest_trade_item (id,trade_id,role,item_id,ord) VALUES ($ti,$tid,'give',$iid,$ord);")
                $ord++
                $ik = "$iid|$($row.npc_name)|$($row.zone_short)"
                if (-not $itemIndex.ContainsKey($ik)) {
                    $itemIndex[$ik] = [ordered]@{ item_id = $iid; npc = [string]$row.npc_name; zone = [string]$row.zone_short; npc_id = $npcId; rewarded = 0; handed = 0 }
                }
                $itemIndex[$ik].handed = 1
            }
            $ord = 0
            foreach ($iid in @($t.reward.reward_item_ids)) {
                $iid = [int]$iid
                if ($iid -le 0) { continue }
                if (-not $itemSet.ContainsKey($iid)) { $stats.skipped_items++; continue }
                $ti = $nextTiId; $nextTiId++
                $sw.WriteLine("INSERT INTO quest_trade_item (id,trade_id,role,item_id,ord) VALUES ($ti,$tid,'reward',$iid,$ord);")
                $ord++
                $ik = "$iid|$($row.npc_name)|$($row.zone_short)"
                if (-not $itemIndex.ContainsKey($ik)) {
                    $itemIndex[$ik] = [ordered]@{ item_id = $iid; npc = [string]$row.npc_name; zone = [string]$row.zone_short; npc_id = $npcId; rewarded = 0; handed = 0 }
                }
                $itemIndex[$ik].rewarded = 1
            }
            $seenHit = @{}
            foreach ($h in @($t.faction_hits)) {
                $fid = [int]$h.faction_id
                if (-not $factionSet.ContainsKey($fid)) { continue }
                $hk = "${fid}:$($h.delta):$($h.line_no)"
                if ($seenHit.ContainsKey($hk)) { continue }
                $seenHit[$hk] = $true
                $npcHitKeys["${fid}:$($h.delta)"] = $true
                $hsf = Escape-Sql ([string]$h.script_file)
                $hln = [int]$h.line_no
                $delta = [int]$h.delta
                $hid = $nextHitId; $nextHitId++
                $sw.WriteLine("INSERT INTO quest_faction_hit (id,quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no) VALUES ($hid,$questNpcId,$tid,'$zn',$npcId,$fid,$delta,'$hsf',$hln);")
                $stats.hits++
            }
        }

        foreach ($h in @($row.faction_hits)) {
            $fid = [int]$h.faction_id
            if (-not $factionSet.ContainsKey($fid)) { continue }
            $hk = "${fid}:$($h.delta)"
            if ($npcHitKeys.ContainsKey($hk)) { continue }
            $npcHitKeys[$hk] = $true
            $hsf = Escape-Sql ([string]$h.script_file)
            $hln = [int]$h.line_no
            $delta = [int]$h.delta
            $hid = $nextHitId; $nextHitId++
            $sw.WriteLine("INSERT INTO quest_faction_hit (id,quest_npc_id,trade_id,zone_short,npc_id,faction_id,delta,script_file,line_no) VALUES ($hid,$questNpcId,0,'$zn',$npcId,$fid,$delta,'$hsf',$hln);")
            $stats.hits++
        }
    }

    foreach ($ik in $itemIndex.Keys) {
        $ix = $itemIndex[$ik]
        $npc = Escape-Sql ([string]$ix.npc)
        $zone = Escape-Sql ([string]$ix.zone)
        $sw.WriteLine(("INSERT INTO quest_items (item_id,npc,zone,npc_id,rewarded,handed) VALUES ({0},'{1}','{2}',{3},{4},{5}) ON DUPLICATE KEY UPDATE rewarded=GREATEST(rewarded,{4}), handed=GREATEST(handed,{5}), npc_id={3};" -f [int]$ix.item_id, $npc, $zone, [int]$ix.npc_id, [int]$ix.rewarded, [int]$ix.handed))
    }
} finally {
    $sw.Close()
}

Write-Host "Loading dump into MySQL..." -ForegroundColor Cyan
$argList = @("-h", $DbHost, "-u", $DbUser, $DbName)
if ($DbPass -ne "") { $argList = @("-h", $DbHost, "-u", $DbUser, "-p$DbPass", $DbName) }
$prevEap = $ErrorActionPreference
$ErrorActionPreference = "Continue"
Get-Content -LiteralPath $dumpPath -Raw | & $MysqlExe @argList 2>&1 | Out-Null
$code = $LASTEXITCODE
$ErrorActionPreference = $prevEap
Remove-Item -LiteralPath $dumpPath -Force -ErrorAction SilentlyContinue
if ($code -ne 0) { throw "Failed loading quest dump (exit $code)" }

Write-Host ("Done. npcs={0} trades={1} says={2} faction_hits={3} faction_reqs={4} skipped_unknown_items={5}" -f $stats.npcs, $stats.trades, $stats.says, $stats.hits, $stats.reqs, $stats.skipped_items) -ForegroundColor Green

