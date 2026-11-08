<?php
/**
 * Interactive Brewall map helpers for allaclone (Konva viewer).
 * Uses $map_txt_dir / $map_txt_url — does not touch Zeal.
 */

if (!function_exists('map_txt_base_path')) {
	function map_txt_base_path($short_name)
	{
		global $map_txt_dir;
		$short_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$short_name);
		if ($short_name === '') {
			return null;
		}
		$path = rtrim($map_txt_dir, '/\\') . DIRECTORY_SEPARATOR . $short_name . '.txt';
		return is_file($path) ? $path : null;
	}
}

if (!function_exists('map_txt_available')) {
	function map_txt_available($short_name)
	{
		return map_txt_base_path($short_name) !== null;
	}
}

if (!function_exists('map_txt_layer_suffixes')) {
	/**
	 * Return layer file suffixes that exist: '' (base), '1', '2', ... up to 10.
	 */
	function map_txt_layer_suffixes($short_name)
	{
		global $map_txt_dir;
		$short_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$short_name);
		$layers = array();
		$dir = rtrim($map_txt_dir, '/\\') . DIRECTORY_SEPARATOR;
		if (is_file($dir . $short_name . '.txt')) {
			$layers[] = '';
		}
		for ($i = 1; $i <= 10; $i++) {
			if (is_file($dir . $short_name . '_' . $i . '.txt')) {
				$layers[] = (string)$i;
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
	 */
	function render_interactive_map($short_name, $spawn_points = array(), $path_points = array(), $roam_boxes = array())
	{
		global $map_txt_url, $root_url;

		$short_name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$short_name);
		if (!map_txt_available($short_name)) {
			return;
		}

		$layers = map_txt_layer_suffixes($short_name);
		$layer_files = array();
		foreach ($layers as $suffix) {
			$layer_files[] = $suffix;
		}

		$safe_points = array();
		foreach ($spawn_points as $pt) {
			if (!isset($pt['x']) || !isset($pt['y'])) {
				continue;
			}
			$entry = array(
				'x' => (float)$pt['x'],
				'y' => (float)$pt['y'],
			);
			if (isset($pt['z'])) {
				$entry['z'] = (float)$pt['z'];
			}
			if (!empty($pt['label'])) {
				$entry['label'] = (string)$pt['label'];
			}
			$safe_points[] = $entry;
		}

		$config = array(
			'containerId' => 'konva-container',
			'controlsId' => 'map-controls',
			'mapBaseUrl' => $map_txt_url,
			'mapBaseName' => $short_name,
			'layerFiles' => $layer_files,
			'spawnPoints' => $safe_points,
			'pathPoints' => $path_points ? $path_points : new stdClass(),
			'roamBoxes' => $roam_boxes,
		);

		echo "<div class='interactive-map-wrap'>\n";
		echo "<div id='map-controls' class='map-controls'>\n";
		echo "<label>Line weight <input type='range' min='1' max='6' value='2' data-map-control='line-weight'></label>\n";
		echo "<label><input type='checkbox' data-map-control='show-pois'> Show POI labels</label>\n";
		echo "<label><input type='checkbox' data-map-control='animate'> Animate spawns</label>\n";
		echo "<label>Floor <select data-map-control='floor'><option value='all'>All floors</option></select></label>\n";

		foreach ($layers as $suffix) {
			$label = ($suffix === '') ? 'Base' : ('Layer ' . $suffix);
			$key = ($suffix === '') ? 'base' : $suffix;
			$checked = ' checked';
			// Base stays on; extra layers on by default
			echo "<label><input type='checkbox' data-map-layer='" . htmlspecialchars($key, ENT_QUOTES) . "'$checked> " . htmlspecialchars($label) . "</label>\n";
		}

		echo "</div>\n";
		echo "<div id='konva-container' class='konva-container' role='img' aria-label='Zone map'></div>\n";
		echo "<p class='map-hint'>Drag to pan, scroll to zoom. Coordinates are /loc order (Y, X). Spawns use canvas = (−x, −y).</p>\n";
		echo "</div>\n";

		$js_base = htmlspecialchars($root_url . 'js/', ENT_QUOTES);
		echo "<script src='" . $js_base . "konva.min.js'></script>\n";
		echo "<script>window.AllacloneMapConfig = " . json_encode($config) . ";</script>\n";
		echo "<script src='" . $js_base . "map_viewer.js'></script>\n";
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
