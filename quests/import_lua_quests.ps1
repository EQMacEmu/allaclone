# Windows wrapper for import_lua_quests.php (the only importer implementation).
# Applies includes/sql/quests.sql and fills quest_* tables from EQEmu *.lua scripts.
#
# Uses a local php.exe when one is on PATH. Otherwise pass -Container to run the importer
# inside a running PHP container that mounts this allaclone checkout at /var/www/html.
#
# Usage:
#   .\import_lua_quests.ps1 -QuestRoot 'D:\eqemu\quests' -DbHost localhost -DbUser root -DbPass root -DbName peq
#   .\import_lua_quests.ps1 -QuestRoot 'D:\eqemu\quests' -Zones qeynos,freporte -DryRun
#   .\import_lua_quests.ps1 -QuestRoot 'D:\eqemu\quests' -Container allaclone-local -DbHost host.docker.internal -DbPass ''

param(
    [Parameter(Mandatory = $true)][string]$QuestRoot,
    [string]$DbHost = "localhost",
    [string]$DbUser = "root",
    [string]$DbPass = "root",
    [string]$DbName = "peq",
    [string[]]$Zones = @(),
    [string]$Container = "",
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"
$QuestRoot = $QuestRoot.TrimEnd('\', '/')
if (-not (Test-Path -LiteralPath $QuestRoot)) {
    throw "Quest root not found: $QuestRoot"
}

$extra = @()
if ($Zones.Count -gt 0) { $extra += "--zones=" + ($Zones -join ',') }
if ($DryRun) { $extra += "--dry-run" }
# Always "--name=value": PowerShell drops empty standalone arguments such as -DbPass ''.
$dbArgs = @("--host=$DbHost", "--user=$DbUser", "--pass=$DbPass", "--db=$DbName")

$php = Get-Command php -ErrorAction SilentlyContinue
if ($php -and -not $Container) {
    & $php.Source (Join-Path $PSScriptRoot 'import_lua_quests.php') "--quests=$QuestRoot" @dbArgs @extra
    exit $LASTEXITCODE
}

if (-not $Container) {
    throw "php was not found on PATH. Install PHP CLI (with mysqli) or pass -Container <name> to run inside a PHP container."
}

# Copy only the zone folders being imported into the container.
$stage = "/tmp/allaclone_quests"
docker exec $Container rm -rf $stage | Out-Null
docker exec $Container mkdir -p $stage | Out-Null
$dirs = Get-ChildItem -LiteralPath $QuestRoot -Directory
if ($Zones.Count -gt 0) {
    $wanted = $Zones | ForEach-Object { $_.ToLower() }
    $dirs = $dirs | Where-Object { $wanted -contains $_.Name.ToLower() }
}
foreach ($d in $dirs) {
    docker cp $d.FullName "${Container}:$stage/$($d.Name)" | Out-Null
}

docker exec $Container php /var/www/html/quests/import_lua_quests.php "--quests=$stage" @dbArgs @extra
exit $LASTEXITCODE
