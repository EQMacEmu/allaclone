<?php
// Development settings for Allaclone.
// maintenance setup copies this to application.php and supplies a random password.
// init/application-config.php opens the database connection for web requests.

$PageTitle = 'Allaclone (development)';
$SiteTitle = 'Allaclone (development)';
$ServerName = 'Development server';
$cfgversion = $current_revision ?? '';
$SiteEmail = '';
$admin = ''; // Optional IP address allowed to use the debug panel.

// localhost uses the Unix socket configured in php.ini.
// maintenance setup creates this database and application user.
$dbhost = 'localhost';
$dbname = 'allaclone';
$dbuser = 'allaclone';
$dbpasswd = 'CHANGE_ME';

// Keep one trailing slash on the installation path.
$root_url = '/';
$site_url = ''; // Optional absolute base URL for canonical metadata.
$includes_url = $root_url . 'includes/';
$includes_dir = '/app/www/includes/';
$quests_dir = '/app/source/quests/'; // Lua checkout used by the importer.
$quests_datas = '/app/www/quests/datas/'; // Bundled .inc fallback pages.
$maps_dir = '/app/www/maps/';
$maps_url = $root_url . 'maps/';
$map_txt_dir = '/app/www/map_txt/';
$map_txt_url = $root_url . 'map_txt/';
$npcs_dir = '/app/www/npcs/';
$npcs_url = $root_url . 'npcs/';
$icons_dir = '/app/www/icons/';
$icons_url = $root_url . 'icons/';
$images_url = $root_url . 'images/';
$charbrowser_url = ''; // Optional external character browser.

$DefaultCSS = '2022';
$CssStyle = $DefaultCSS;

// IDs to exclude from public results.
$hide_npc_id = array();
$hide_item_id = array();
$hide_spell_id = array();
$IgnoreZones = array('load', 'loading', 'load2', 'nektropos', 'arttest',
    'apprentice', 'tutorial', 'cshome');

// Level and search limits.
$ServerMaxLevel = 65;
$ServerMaxNPCLevel = 95;
$MaxFactionsReturned = 50;
$MaxItemsReturned = 50;
$MaxNpcsReturned = 50;
$SpawngroupAroundRange = 100;
// Faction-result pagination.
$MaxResultsPerPage = 50;
$AdjacentPages = 3;

// Enable quest features after running update-quests.
$EnableNews = false; // Requires a separately maintained eqbnews table.
$AllowQuestsNPC = false;
$ItemQuestSource = false;
$AllowEpics = false;
$AlwaysBuildQuest = false; // Never generate quests during web requests.

$ShowNPCFactionHits = true;
$ShowNpcDropChances = false;
$MerchantsDontDropStuff = true;
$ShowNPCNumberInZoneLevelList = false;
$SortZoneLevelList = true;
$GroupNpcsByName = true;
$HideInvisibleMen = true;
$ItemAddChanceToDrop = false;
$ShowNpcsAttackSpeed = false;
$ShowNpcsAverageDamages = false;
$DisplayNamedNPCsInfo = false;
$DisplayNPCStats = false;
$DisplaySpawnGroupInfo = true;
$DisplayTaskInfo = false;
$DisplayTaskActivities = false;
$AllowCSVOutputOptions = false;
$ItemFoundInfo = true;
$UseSpellGlobals = false;
$UseZAMSearch = false;
$UseCustomZoneList = false;
$ShowHeader = true;
$UseWikiImages = false;

// Player and account features require additional data.
$MaxRecentlyDiscoveredItems = 0;
$DiscoveredItemsMaxStatus = -1;
$DiscoveredItemsOnly = false;
$ShowAccount = false;
$ShowCharacters = false;
