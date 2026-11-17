<?php
/**
 * Interactive Brewall map helpers for allaclone (Konva viewer).
 * Map files are read from $map_txt_dir and served from $map_txt_url.
 */

if (!function_exists('map_txt_index')) {
	/**
	 * Lowercase filename => on-disk filename for every .txt in $map_txt_dir.
	 * Brewall packs mix case (Qeynos_1.txt), which matters on case-sensitive filesystems.
	 */
	function map_txt_index()
	{
		global $map_txt_dir;
		static $index = null;
		if ($index !== null) {
			return $index;
		}
		$index = array();
		$dir = rtrim((string)$map_txt_dir, '/\\');
		if ($dir === '' || !is_dir($dir)) {
			return $index;
		}
		foreach (scandir($dir) as $file) {
			if (strcasecmp(substr($file, -4), '.txt') === 0) {
				$index[strtolower($file)] = $file;
			}
		}
		return $index;
	}
}

if (!function_exists('map_txt_file')) {
	/** On-disk filename for a zone map file, or null. $suffix '' is the base file. */
	function map_txt_file($short_name, $suffix = '')
	{
		$short_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$short_name);
		if ($short_name === '') {
			return null;
		}
		$name = strtolower($short_name . ($suffix === '' ? '' : '_' . $suffix) . '.txt');
		$index = map_txt_index();
		return isset($index[$name]) ? $index[$name] : null;
	}
}

if (!function_exists('map_txt_available')) {
	function map_txt_available($short_name)
	{
		return map_txt_file($short_name) !== null;
	}
}

if (!function_exists('map_txt_layers')) {
	/**
	 * Existing map files for a zone: list of ['key' => 'base'|'1'..'10', 'file' => on-disk name].
	 */
	function map_txt_layers($short_name)
	{
		$layers = array();
		$base = map_txt_file($short_name);
		if ($base !== null) {
			$layers[] = array('key' => 'base', 'file' => $base);
		}
		for ($i = 1; $i <= 10; $i++) {
			$file = map_txt_file($short_name, (string)$i);
			if ($file !== null) {
				$layers[] = array('key' => (string)$i, 'file' => $file);
			}
		}
		return $layers;
	}
}

if (!function_exists('map_world_to_canvas')) {
	/** Documented transform: canvas = (-spawn2.x, -spawn2.y). */
	function map_world_to_canvas($x, $y)
	{
		return array('x' => -1.0 * (float)$x, 'y' => -1.0 * (float)$y);
	}
}

if (!function_exists('render_interactive_map')) {
	/**
	 * Emit map controls + Konva container + boot config.
	 *
	 * @param string $short_name zone short name
	 * @param array  $spawn_points list of ['x'=>,'y'=>,'z'=>optional,'label'=>optional] in spawn2 /loc axes
	 * @param array  $path_points  optional map of path id => list of {x,y}
	 * @param array  $roam_boxes   optional list of min_x,max_x,min_y,max_y (spawn2/loc axes)
	 * @param string $fit          initial view: 'spawns' (frame spawn points) or 'zone' (whole map)
	 */
	function render_interactive_map($short_name, $spawn_points = array(), $path_points = array(), $roam_boxes = array(), $fit = 'spawns')
	{
		global $map_txt_url, $root_url;
		static $map_render_seq = 0;
		static $map_scripts_emitted = false;

		$short_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$short_name);
		if (!map_txt_available($short_name)) {
			return;
		}

		$layers = map_txt_layers($short_name);

		$safe_points = array();
		foreach ($spawn_points as $pt) {
			if (!isset($pt['x']) || !isset($pt['y'])) {
				continue;
			}
			$entry = array(
				'x' => (float)$pt['x'],
				'y' => (float)$pt['y'],
			);
			if (isset($pt['z']) && is_numeric($pt['z'])) {
				$entry['z'] = (float)$pt['z'];
			}
			if (!empty($pt['label'])) {
				$entry['label'] = (string)$pt['label'];
			}
			$safe_points[] = $entry;
		}

		// Floor list is filled in JS from Brewall color levels in the map files.
		// Stepped Z fade: 0/5/10/15/20/50/off (index 6 = off). Start off.
		$z_fade_step = 6;

		$map_render_seq++;
		$suffix = '-' . $map_render_seq;
		$container_id = 'konva-container' . $suffix;
		$controls_id = 'map-controls' . $suffix;

		$config = array(
			'containerId' => $container_id,
			'controlsId' => $controls_id,
			'mapBaseUrl' => $map_txt_url,
			'layerFiles' => $layers,
			'spawnPoints' => $safe_points,
			'pathPoints' => $path_points ? $path_points : new stdClass(),
			'roamBoxes' => $roam_boxes,
			'fit' => ($fit === 'zone') ? 'zone' : 'spawns',
			'zFadeStep' => $z_fade_step,
		);

		echo "<div class='interactive-map-wrap'>\n";
		echo "<div id='" . htmlspecialchars($controls_id, ENT_QUOTES) . "' class='map-controls'>\n";
		echo "<label>Line weight <input type='range' min='1' max='6' value='2' data-map-control='line-weight'></label>\n";
		if (count($safe_points) > 0) {
			echo "<label><input type='checkbox' data-map-control='animate'> Animate spawns</label>\n";
		}
		echo "<label data-map-control-wrap='z-fade' class='map-z-fade' title='Off-level opacity: 0 / 5 / 10 / 15 / 20 / 50 / off'>";
		echo "Z fade <input type='range' min='0' max='6' step='1' value='" . (int)$z_fade_step . "' data-map-control='z-fade'>";
		echo " <span data-map-control='z-fade-value'>off</span></label>\n";
		echo "<label data-map-control-wrap='z-floor' class='map-z-floor' hidden>Floor ";
		echo "<button type='button' data-map-control='z-prev' title='Previous floor'>&lt;</button> ";
		echo "<select data-map-control='z-focus'></select> ";
		echo "<button type='button' data-map-control='z-next' title='Next floor'>&gt;</button>";
		echo "</label>\n";

		// _1 files in Brewall packs hold the place-name labels; they start hidden to keep the map readable.
		foreach ($layers as $layer) {
			if ($layer['key'] === 'base') {
				$label = 'Map';
				$checked = ' checked';
			} elseif ($layer['key'] === '1') {
				$label = 'Labels';
				$checked = '';
			} else {
				$label = 'Layer ' . $layer['key'];
				$checked = ' checked';
			}
			echo "<label><input type='checkbox' data-map-layer='" . htmlspecialchars($layer['key'], ENT_QUOTES) . "'$checked> " . htmlspecialchars($label) . "</label>\n";
		}
		echo "<button type='button' data-map-control='reset'>Reset view</button>\n";

		echo "</div>\n";
		echo "<div id='" . htmlspecialchars($container_id, ENT_QUOTES) . "' class='konva-container' role='img' aria-label='Zone map' tabindex='0'></div>\n";
		$hint = 'Drag to pan. Click the map, then scroll to zoom (or hold Ctrl and scroll). The readout shows /loc coordinates (Y, X).';
		$hint .= ' Use Floor to step through Brewall map levels; click a spawn ring to focus that height.';
		$hint .= ' Z fade steps off-level opacity: 0 / 5 / 10 / 15 / 20 / 50 / off.';
		echo "<p class='map-hint'>" . htmlspecialchars($hint) . "</p>\n";
		echo "</div>\n";

		$js_base = htmlspecialchars($root_url . 'js/', ENT_QUOTES);
		echo "<script>window.AllacloneMapConfigs = window.AllacloneMapConfigs || [];";
		echo "window.AllacloneMapConfigs.push(" . json_encode($config) . ");";
		echo "window.AllacloneMapConfig = window.AllacloneMapConfigs[window.AllacloneMapConfigs.length - 1];</script>\n";
		if (!$map_scripts_emitted) {
			echo "<script src='" . $js_base . "konva.min.js'></script>\n";
			echo "<script src='" . $js_base . "map_viewer.js'></script>\n";
			$map_scripts_emitted = true;
		}
	}
}

if (!function_exists('fetch_npc_map_spawns')) {
	/**
	 * Collect spawn2 points for an NPC in a zone (spawn2 x/y = loc X/Y).
	 */
	function fetch_npc_map_spawns($npc_id, $zone_short)
	{
		global $db, $tbspawn2, $tbspawnentry;

		$npc_id = (int)$npc_id;
		$zone_short = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$zone_short);
		if ($npc_id <= 0 || $zone_short === '') {
			return array();
		}

		$spawnentryfilter = function_exists('gatefilter') ? gatefilter(array($tbspawnentry, $tbspawn2)) : '';
		$query = "SELECT DISTINCT $tbspawn2.x, $tbspawn2.y, $tbspawn2.z
			FROM $tbspawn2
			JOIN $tbspawnentry ON $tbspawnentry.spawngroupID = $tbspawn2.spawngroupID
			WHERE $tbspawnentry.npcID = $npc_id
			AND $tbspawn2.zone = '" . addslashes($zone_short) . "'
			$spawnentryfilter
			LIMIT 256";
		$result = mysqli_query($db, $query);
		if (!$result) {
			return array();
		}
		$points = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$points[] = array(
				'x' => (float)$row['x'],
				'y' => (float)$row['y'],
				'z' => (float)$row['z'],
			);
		}
		return $points;
	}
}

if (!function_exists('fetch_npc_quest_map_spawns')) {
	/**
	 * Literal quest-script spawn points (eq.spawn2 / unique_spawn) for an NPC in a zone.
	 */
	function fetch_npc_quest_map_spawns($npc_id, $zone_short)
	{
		global $db, $tbquestspawn;

		$npc_id = (int)$npc_id;
		$zone_short = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$zone_short);
		if ($npc_id <= 0 || $zone_short === '' || empty($tbquestspawn)) {
			return array();
		}
		$res = @mysqli_query($db, "SHOW TABLES LIKE '" . addslashes($tbquestspawn) . "'");
		if (!$res || mysqli_num_rows($res) === 0) {
			if ($res) {
				mysqli_free_result($res);
			}
			return array();
		}
		mysqli_free_result($res);

		$query = "SELECT DISTINCT x, y, z
			FROM $tbquestspawn
			WHERE npc_id = $npc_id
			AND zone_short = '" . addslashes($zone_short) . "'
			LIMIT 256";
		$result = mysqli_query($db, $query);
		if (!$result) {
			return array();
		}
		$points = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$points[] = array(
				'x' => (float)$row['x'],
				'y' => (float)$row['y'],
				'z' => (float)$row['z'],
			);
		}
		mysqli_free_result($result);
		return $points;
	}
}

if (!function_exists('fetch_npc_display_map_spawns')) {
	/**
	 * Prefer static spawn2 points; fall back to quest-script spawn coords.
	 * @return array{source: string|null, points: array}
	 */
	function fetch_npc_display_map_spawns($npc_id, $zone_short)
	{
		$points = fetch_npc_map_spawns($npc_id, $zone_short);
		if (count($points) > 0) {
			return array('source' => 'spawn2', 'points' => $points);
		}
		$points = fetch_npc_quest_map_spawns($npc_id, $zone_short);
		if (count($points) > 0) {
			return array('source' => 'quest', 'points' => $points);
		}
		return array('source' => null, 'points' => array());
	}
}

if (!function_exists('fetch_zone_succor_spawn')) {
	function fetch_zone_succor_spawn($zone_row)
	{
		if (!is_array($zone_row)) {
			return array();
		}
		if (!isset($zone_row['safe_x']) || !isset($zone_row['safe_y'])) {
			return array();
		}
		return array(array(
			'x' => (float)$zone_row['safe_x'],
			'y' => (float)$zone_row['safe_y'],
			'z' => isset($zone_row['safe_z']) ? (float)$zone_row['safe_z'] : 0,
			'label' => 'Succor',
		));
	}
}
