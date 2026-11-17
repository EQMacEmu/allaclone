/**
 * Allaclone Brewall map viewer (Konva).
 * Map-file coords are Brewall model space: canvas = (-spawn2.x, -spawn2.y).
 * Mouseover /loc: X = -canvasX, Y = -canvasY.
 * Z-fade mirrors Zeal ZoneMap autofade: opacity vs focus Z (spawn/succor).
 */
(function (global) {
  'use strict';

  var MIN_SCALE = 0.05;
  var MAX_SCALE = 8;
  // Smallest area (map units) framed around spawn points so a lone spawn keeps surrounding context.
  var MIN_SPAWN_FRAME = 600;
  // Match Zeal ZoneMap defaults (kDefaultZLevelHeightScale / MapFadedZLevelAlpha).
  var Z_HEIGHT_SCALE = 10;
  // Stepped off-level opacity % (null = off). Dense in the useful 0–20 band.
  var Z_FADE_STEPS = [0, 5, 10, 15, 20, 50, null];
  var Z_FADE_STEP_OFF = Z_FADE_STEPS.length - 1;
  var Z_FADE_STEP_DEFAULT_ON = 4; // 20%
  var SPAWN_OFFLEVEL_OPACITY = 0.35;
  // Pad around per-floor line AABB when assigning spawns (localized Brewall wings, e.g. ToV L-3).
  var FLOOR_XY_PAD = 40;
  // Spawn ring outerRadius in map units (see _drawSpawns). Screen size follows the map,
  // then clamps so zoom-out stays readable without covering half the zone.
  var SPAWN_OUTER_RADIUS = 18;
  var SPAWN_MIN_SCREEN_PX = 12;
  var SPAWN_MAX_SCREEN_PX = 30;
  var INVALID_LEVEL_ID = -128;
  // Brewall mapping-standards colors → level ids (Zeal maps_to_cpp.py).
  var BREWALL_LEVEL_LUT = {
    '255,168,102': 6,
    '204,102,0': 5,
    '102,255,102': 4,
    '0,204,0': 3,
    '102,255,255': 2,
    '0,204,204': 1,
    '0,0,0': 0,
    '255,102,255': -1,
    '204,0,204': -2,
    '178,102,255': -3,
    '102,0,204': -4
  };

  function rgbCss(r, g, b) {
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  function brewLevelId(r, g, b) {
    var key = r + ',' + g + ',' + b;
    return Object.prototype.hasOwnProperty.call(BREWALL_LEVEL_LUT, key)
      ? BREWALL_LEVEL_LUT[key]
      : INVALID_LEVEL_ID;
  }

  function parseMapContent(content) {
    var lines = [];
    var points = [];
    var rows = content.split(/\r?\n/);
    for (var i = 0; i < rows.length; i++) {
      var raw = rows[i].trim();
      if (!raw) continue;
      var type = raw.charAt(0);
      if (type !== 'L' && type !== 'P') continue;
      var parts = raw.substring(1).split(',');
      if (type === 'L' && parts.length >= 9) {
        var r = parseInt(parts[6], 10) || 0;
        var g = parseInt(parts[7], 10) || 0;
        var b = parseInt(parts[8], 10) || 0;
        lines.push({
          points: [parseFloat(parts[0]), parseFloat(parts[1]), parseFloat(parts[3]), parseFloat(parts[4])],
          z0: parseFloat(parts[2]),
          z1: parseFloat(parts[5]),
          color: rgbCss(r, g, b),
          levelId: brewLevelId(r, g, b)
        });
      } else if (type === 'P' && parts.length >= 8) {
        points.push({
          x: parseFloat(parts[0]),
          y: parseFloat(parts[1]),
          z: parseFloat(parts[2]),
          color: rgbCss(parseInt(parts[3], 10) || 0, parseInt(parts[4], 10) || 0, parseInt(parts[5], 10) || 0),
          label: parts.slice(7).join(',').trim().replace(/_/g, ' ')
        });
      }
    }
    return { lines: lines, points: points };
  }

  function spawnToCanvas(spawn) {
    // spawn.x / spawn.y are spawn2 (loc X / loc Y)
    return { x: -Number(spawn.x), y: -Number(spawn.y), z: spawn.z, label: spawn.label || '' };
  }

  function emptyBounds() {
    return { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity };
  }

  function boundsValid(b) {
    return b.minX <= b.maxX && b.minY <= b.maxY;
  }

  function addToBounds(b, x, y) {
    if (!isFinite(x) || !isFinite(y)) return;
    if (x < b.minX) b.minX = x;
    if (x > b.maxX) b.maxX = x;
    if (y < b.minY) b.minY = y;
    if (y > b.maxY) b.maxY = y;
  }

  function hasFiniteZ(z) {
    return typeof z === 'number' && isFinite(z);
  }

  function clampZFadeStep(idx) {
    idx = parseInt(idx, 10);
    if (!isFinite(idx) || idx < 0) return Z_FADE_STEP_OFF;
    if (idx >= Z_FADE_STEPS.length) return Z_FADE_STEP_OFF;
    return idx;
  }

  function zFadeStepLabel(idx) {
    var pct = Z_FADE_STEPS[clampZFadeStep(idx)];
    return pct == null ? 'off' : pct + '%';
  }

  /** Zeal render_get_line_color_and_opacity autofade: distance of focusZ to [z0,z1]. */
  function lineOpacity(z0, z1, focusZ, scale, faded) {
    if (!hasFiniteZ(focusZ) || !hasFiniteZ(z0) || !hasFiniteZ(z1)) return 1;
    var lineMax = Math.max(z0, z1);
    var lineMin = Math.min(z0, z1);
    var maxDelta = Math.max(lineMax - focusZ, focusZ - lineMax);
    var minDelta = Math.max(lineMin - focusZ, focusZ - lineMin);
    var minDistance = Math.min(maxDelta, minDelta);
    if (minDistance >= 2 * scale) return faded;
    if (minDistance > scale) {
      var fraction = minDistance / scale; // 1..2
      return faded + (1 - faded) * (2 - fraction);
    }
    return 1;
  }

  function pointOpacity(z, focusZ, scale, faded) {
    return lineOpacity(z, z, focusZ, scale, faded);
  }

  function formatZ(z) {
    if (!hasFiniteZ(z)) return '?';
    return Math.abs(z - Math.round(z)) < 0.05 ? String(Math.round(z)) : z.toFixed(1);
  }

  function floorLabel(level) {
    if (level.kind === 'z') {
      return 'Z ≈ ' + formatZ(level.mid);
    }
    // Uniform Brewall ids: L-3, L0, L+1 (no special "Main" label).
    var name = 'L' + (level.id > 0 ? '+' : '') + level.id;
    return name + ' (Z ' + formatZ(level.min) + '…' + formatZ(level.max) + ')';
  }

  /** Median of numeric samples (sorted copy). */
  function medianZ(samples) {
    if (!samples || !samples.length) return 0;
    var zs = samples.slice().sort(function (a, b) { return a - b; });
    var mid = Math.floor(zs.length / 2);
    return zs.length % 2 ? zs[mid] : (zs[mid - 1] + zs[mid]) / 2;
  }

  /** Brewall color floors present in geometry; fallback = dense Z bands when colors are missing. */
  function finalizeMapFloors(levelStats, lineMids) {
    var floors = [];
    var id;
    for (id in levelStats) {
      if (!Object.prototype.hasOwnProperty.call(levelStats, id)) continue;
      var sid = parseInt(id, 10);
      if (sid === INVALID_LEVEL_ID) continue;
      var st = levelStats[id];
      if (!st || st.count < 1) continue;
      // Median focus Z — (min+max)/2 is useless for bloated floors (Vex Thal L0 -102…64).
      var floor = {
        kind: 'level',
        id: sid,
        min: st.min,
        max: st.max,
        mid: st.zMids && st.zMids.length ? medianZ(st.zMids) : (st.min + st.max) / 2,
        count: st.count
      };
      if (st.minX <= st.maxX && st.minY <= st.maxY) {
        floor.minX = st.minX;
        floor.maxX = st.maxX;
        floor.minY = st.minY;
        floor.maxY = st.maxY;
      }
      floors.push(floor);
    }
    // Low → high so Floor < / > and the dropdown match elevation order.
    floors.sort(function (a, b) { return a.id - b.id; });
    if (floors.length >= 2) return floors;

    // Fallback: cluster line mid-Z so single-color maps still get floor steps.
    var zs = (lineMids || []).slice().sort(function (a, b) { return a - b; });
    if (zs.length < 20) return floors;
    var scale = Z_HEIGHT_SCALE * 2;
    var minCount = Math.max(15, Math.floor(zs.length * 0.02));
    var clusters = [];
    var i;
    for (i = 0; i < zs.length; i++) {
      var z = zs[i];
      if (!clusters.length || Math.abs(z - clusters[clusters.length - 1].mid) >= scale) {
        clusters.push({ kind: 'z', id: clusters.length, min: z, max: z, mid: z, count: 1, _sum: z });
      } else {
        var c = clusters[clusters.length - 1];
        c.min = Math.min(c.min, z);
        c.max = Math.max(c.max, z);
        c._sum += z;
        c.count += 1;
        c.mid = c._sum / c.count;
      }
    }
    var dense = [];
    for (i = 0; i < clusters.length; i++) {
      if (clusters[i].count >= minCount) {
        delete clusters[i]._sum;
        dense.push(clusters[i]);
      }
    }
    dense.sort(function (a, b) { return a.mid - b.mid; });
    return dense.length >= 2 ? dense : floors;
  }

  function AllacloneMap(options) {
    this.opts = options || {};
    this.container = document.getElementById(this.opts.containerId || 'konva-container');
    if (!this.container || typeof Konva === 'undefined') {
      return;
    }
    this.mapBaseUrl = this.opts.mapBaseUrl || 'map_txt/';
    this.layerFiles = this.opts.layerFiles || [];
    this.spawnPoints = (this.opts.spawnPoints || []).map(spawnToCanvas);
    this.pathPoints = this.opts.pathPoints || {};
    this.roamBoxes = this.opts.roamBoxes || [];
    this.fit = this.opts.fit === 'zone' ? 'zone' : 'spawns';
    this.strokeWidth = 2;
    this.wheelActive = false;
    this.fileLayers = {};
    this.spawnRings = [];
    this.spawnLabelNodes = [];
    this.spawnAnim = null;
    this.bounds = emptyBounds();
    this.focusSpawnIndex = -1;
    this.levelStats = {};
    this.lineMids = [];
    this.mapFloors = [];
    this.focusFloorIndex = -1;
    this.focusLevelId = null;
    this.zFadeStepIndex = clampZFadeStep(
      this.opts.zFadeStep != null ? this.opts.zFadeStep : Z_FADE_STEP_OFF
    );
    this.focusZ = this._defaultFocusZ();
    this._initStage();
    this._bindControls();
    this._loadAll();
  }

  AllacloneMap.prototype._defaultFocusZ = function () {
    var i;
    for (i = 0; i < this.spawnPoints.length; i++) {
      if (hasFiniteZ(this.spawnPoints[i].z)) {
        this.focusSpawnIndex = i;
        return this.spawnPoints[i].z;
      }
    }
    return null;
  };

  AllacloneMap.prototype._recordLineLevel = function (line) {
    var z0 = line.z0;
    var z1 = line.z1;
    if (hasFiniteZ(z0) && hasFiniteZ(z1)) {
      this.lineMids.push((z0 + z1) / 2);
    }
    var id = line.levelId;
    if (id === INVALID_LEVEL_ID) return;
    var st = this.levelStats[id];
    if (!st) {
      st = {
        min: Infinity,
        max: -Infinity,
        minX: Infinity,
        maxX: -Infinity,
        minY: Infinity,
        maxY: -Infinity,
        zMids: [],
        count: 0
      };
      this.levelStats[id] = st;
    }
    st.min = Math.min(st.min, z0, z1);
    st.max = Math.max(st.max, z0, z1);
    if (hasFiniteZ(z0) && hasFiniteZ(z1)) st.zMids.push((z0 + z1) / 2);
    var p = line.points;
    if (p && p.length >= 4) {
      addToBounds(st, p[0], p[1]);
      addToBounds(st, p[2], p[3]);
    }
    st.count += 1;
  };

  AllacloneMap.prototype._initStage = function () {
    this.stage = new Konva.Stage({
      container: this.container,
      width: this.container.clientWidth || 640,
      height: this.container.clientHeight || 480,
      draggable: true
    });

    this.gridLayer = new Konva.Layer({ name: 'grid', listening: false });
    this.pathLayer = new Konva.Layer({ name: 'paths', listening: false });
    this.spawnLayer = new Konva.Layer({ name: 'spawns' });
    this.stage.add(this.gridLayer);
    this.stage.add(this.pathLayer);
    this.stage.add(this.spawnLayer);

    this._coordEl = document.createElement('div');
    this._coordEl.className = 'map-coord-display';
    this._coordEl.style.display = 'none';
    this.container.appendChild(this._coordEl);

    this._creditEl = document.createElement('div');
    this._creditEl.className = 'map-credit';
    this._creditEl.innerHTML = 'Map geometry: <a href="https://www.eqmaps.info/" target="_blank" rel="noopener">Brewall</a>';
    this.container.appendChild(this._creditEl);

    var self = this;
    this.stage.on('mousemove', function () {
      var pos = self.stage.getPointerPosition();
      if (!pos) return;
      var mapPos = self.stage.getAbsoluteTransform().copy().invert().point(pos);
      self._coordEl.style.display = '';
      self._coordEl.textContent = 'Y: ' + (-mapPos.y).toFixed(1) + ', X: ' + (-mapPos.x).toFixed(1);
    });
    this.stage.on('mouseleave', function () {
      self._coordEl.style.display = 'none';
    });

    // Page scrolling passes over the map until the user clicks into it (or holds Ctrl).
    this.container.addEventListener('mousedown', function () {
      self._setWheelActive(true);
    });
    document.addEventListener('mousedown', function (e) {
      if (!self.container.contains(e.target)) self._setWheelActive(false);
    });

    this.container.addEventListener('wheel', function (e) {
      if (!self.wheelActive && !e.ctrlKey) return;
      e.preventDefault();
      var rect = self.container.getBoundingClientRect();
      self._zoomAt({ x: e.clientX - rect.left, y: e.clientY - rect.top }, e.deltaY > 0 ? 0.9 : 1.1);
    }, { passive: false });

    var resizeTimer = null;
    global.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () { self._resize(); }, 100);
    });
  };

  AllacloneMap.prototype._setWheelActive = function (active) {
    this.wheelActive = active;
    this.container.classList.toggle('map-active', active);
  };

  AllacloneMap.prototype._zoomAt = function (pointer, factor) {
    var oldScale = this.stage.scaleX();
    var newScale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, oldScale * factor));
    var mousePointTo = {
      x: (pointer.x - this.stage.x()) / oldScale,
      y: (pointer.y - this.stage.y()) / oldScale
    };
    this.stage.scale({ x: newScale, y: newScale });
    this.stage.position({
      x: pointer.x - mousePointTo.x * newScale,
      y: pointer.y - mousePointTo.y * newScale
    });
    this._syncSpawnScreenScale(1);
    this.stage.batchDraw();
  };

  AllacloneMap.prototype._resize = function () {
    var w = this.container.clientWidth;
    var h = this.container.clientHeight;
    if (!w || !h || (w === this.stage.width() && h === this.stage.height())) return;
    // Keep the same map point at the center of the view.
    var scale = this.stage.scaleX();
    var center = {
      x: (this.stage.width() / 2 - this.stage.x()) / scale,
      y: (this.stage.height() / 2 - this.stage.y()) / scale
    };
    this.stage.size({ width: w, height: h });
    this.stage.position({ x: w / 2 - center.x * scale, y: h / 2 - center.y * scale });
    this._syncSpawnScreenScale(1);
    this.stage.batchDraw();
  };

  // Keep spawn rings/labels readable across zoom without locking a fixed screen size.
  AllacloneMap.prototype._syncSpawnScreenScale = function (pulse) {
    if (!this.stage) return;
    var stageScale = this.stage.scaleX();
    if (!stageScale) return;
    var p = (pulse == null || !(pulse > 0)) ? 1 : pulse;
    var naturalScreen = SPAWN_OUTER_RADIUS * stageScale;
    var targetScreen = naturalScreen;
    if (targetScreen < SPAWN_MIN_SCREEN_PX) targetScreen = SPAWN_MIN_SCREEN_PX;
    if (targetScreen > SPAWN_MAX_SCREEN_PX) targetScreen = SPAWN_MAX_SCREEN_PX;
    var scale = (targetScreen / (SPAWN_OUTER_RADIUS * stageScale)) * p;
    var labelOff = 20 / stageScale;
    var labelUp = 8 / stageScale;
    for (var r = 0; r < this.spawnRings.length; r++) {
      this.spawnRings[r].scale({ x: scale, y: scale });
    }
    for (var t = 0; t < this.spawnLabelNodes.length; t++) {
      var lab = this.spawnLabelNodes[t];
      var idx = lab.getAttr('spawnIndex');
      var pt = this.spawnPoints[idx];
      if (!pt) continue;
      lab.scale({ x: scale, y: scale });
      lab.position({ x: pt.x + labelOff, y: pt.y - labelUp });
    }
  };

  AllacloneMap.prototype._bindControls = function () {
    var self = this;
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    if (!root) return;

    var weight = root.querySelector('[data-map-control="line-weight"]');
    if (weight) {
      weight.addEventListener('input', function () {
        self.strokeWidth = parseInt(weight.value, 10) || 2;
        self._applyStrokeWidth();
        self._applyZFade();
      });
    }

    var animToggle = root.querySelector('[data-map-control="animate"]');
    if (animToggle) {
      animToggle.addEventListener('change', function () {
        self._setSpawnAnim(!!animToggle.checked);
      });
    }

    var zFade = root.querySelector('[data-map-control="z-fade"]');
    var zFadeVal = root.querySelector('[data-map-control="z-fade-value"]');
    if (zFade) {
      zFade.min = '0';
      zFade.max = String(Z_FADE_STEPS.length - 1);
      zFade.step = '1';
      zFade.value = String(this.zFadeStepIndex);
      if (zFadeVal) zFadeVal.textContent = zFadeStepLabel(this.zFadeStepIndex);
      zFade.addEventListener('input', function () {
        self._setZFadeStep(zFade.value, false);
      });
    }

    var zFocus = root.querySelector('[data-map-control="z-focus"]');
    if (zFocus) {
      zFocus.addEventListener('change', function () {
        var idx = parseInt(zFocus.value, 10);
        if (!isFinite(idx)) return;
        self._setFocusFloor(idx, true);
      });
    }

    var zPrev = root.querySelector('[data-map-control="z-prev"]');
    if (zPrev) {
      zPrev.addEventListener('click', function () {
        self._stepFocusFloor(-1);
      });
    }
    var zNext = root.querySelector('[data-map-control="z-next"]');
    if (zNext) {
      zNext.addEventListener('click', function () {
        self._stepFocusFloor(1);
      });
    }

    var reset = root.querySelector('[data-map-control="reset"]');
    if (reset) {
      reset.addEventListener('click', function () {
        self._fitView();
      });
    }

    var fileToggles = root.querySelectorAll('[data-map-layer]');
    for (var i = 0; i < fileToggles.length; i++) {
      (function (el) {
        el.addEventListener('change', function () {
          var layer = self.fileLayers[el.getAttribute('data-map-layer')];
          if (layer) {
            layer.visible(!!el.checked);
            self.stage.batchDraw();
          }
        });
      })(fileToggles[i]);
    }
  };

  AllacloneMap.prototype._nearestSpawnIndex = function (z) {
    var best = -1;
    var bestDist = Infinity;
    for (var i = 0; i < this.spawnPoints.length; i++) {
      var sz = this.spawnPoints[i].z;
      if (!hasFiniteZ(sz)) continue;
      var d = Math.abs(sz - z);
      if (d < bestDist) {
        bestDist = d;
        best = i;
      }
    }
    return best;
  };

  AllacloneMap.prototype._floorHasXy = function (floor) {
    return !!(floor && isFinite(floor.minX) && isFinite(floor.maxX) && isFinite(floor.minY) &&
      isFinite(floor.maxY) && floor.minX <= floor.maxX && floor.minY <= floor.maxY);
  };

  /** True when map-space (x,y) sits in the floor's line AABB (padded). No AABB → unrestricted. */
  AllacloneMap.prototype._floorXyContains = function (floor, x, y) {
    if (!this._floorHasXy(floor)) return true;
    var pad = FLOOR_XY_PAD;
    return x >= floor.minX - pad && x <= floor.maxX + pad && y >= floor.minY - pad &&
      y <= floor.maxY + pad;
  };

  AllacloneMap.prototype._scoreFloorForZ = function (floor, z) {
    if (z >= floor.min && z <= floor.max) {
      // Prefer the tightest band that contains z.
      return (floor.max - floor.min) * 0.001;
    }
    if (z > floor.max) {
      // Above the band (e.g. Z 74 vs L0/L+1 max 64): prefer the higher floor on a tie.
      return 1000 + (z - floor.max) - (floor.kind === 'level' ? floor.id : floor.mid) * 0.0001;
    }
    // Below the band: prefer the lower floor on a tie.
    return 1000 + (floor.min - z) + (floor.kind === 'level' ? floor.id : floor.mid) * 0.0001;
  };

  AllacloneMap.prototype._bestFloorIndexForZ = function (z) {
    if (!hasFiniteZ(z) || !this.mapFloors.length) return -1;
    var best = -1;
    var bestScore = Infinity;
    for (var i = 0; i < this.mapFloors.length; i++) {
      var score = this._scoreFloorForZ(this.mapFloors[i], z);
      if (score < bestScore) {
        bestScore = score;
        best = i;
      }
    }
    return best;
  };

  /**
   * Pick floor for a spawn using Z scoring, but skip localized Brewall floors whose
   * line AABB does not contain the spawn XY (ToV L-3 wing vs Dozekar same-Z).
   */
  AllacloneMap.prototype._bestFloorIndexForSpawn = function (pt) {
    if (!pt || !hasFiniteZ(pt.z) || !this.mapFloors.length) return -1;
    var best = -1;
    var bestScore = Infinity;
    for (var i = 0; i < this.mapFloors.length; i++) {
      var f = this.mapFloors[i];
      if (!this._floorXyContains(f, pt.x, pt.y)) continue;
      var score = this._scoreFloorForZ(f, pt.z);
      if (score < bestScore) {
        bestScore = score;
        best = i;
      }
    }
    return best;
  };

  /** Spawn is on the selected Brewall floor when Z matches and XY is in that floor's region. */
  AllacloneMap.prototype._spawnOnSelectedFloor = function (pt, floor) {
    if (!pt || !hasFiniteZ(pt.z) || !floor) return true;
    if (pt.z < floor.min - Z_HEIGHT_SCALE || pt.z > floor.max + Z_HEIGHT_SCALE) return false;
    return this._floorXyContains(floor, pt.x, pt.y);
  };

  AllacloneMap.prototype._zFadePreset = function () {
    return Z_FADE_STEPS[clampZFadeStep(this.zFadeStepIndex)];
  };

  AllacloneMap.prototype._zFadeEnabled = function () {
    return this._zFadePreset() != null;
  };

  AllacloneMap.prototype._zFadedAlpha = function () {
    var pct = this._zFadePreset();
    return pct == null ? 1 : pct / 100;
  };

  AllacloneMap.prototype._setZFadeStep = function (index, syncSlider) {
    this.zFadeStepIndex = clampZFadeStep(index);
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    if (root) {
      var zFade = root.querySelector('[data-map-control="z-fade"]');
      var zFadeVal = root.querySelector('[data-map-control="z-fade-value"]');
      if (syncSlider !== false && zFade) zFade.value = String(this.zFadeStepIndex);
      if (zFadeVal) zFadeVal.textContent = zFadeStepLabel(this.zFadeStepIndex);
    }
    this._applyZFade();
  };

  /** If fade is off, snap to the useful 20% preset (floor/spawn focus). */
  AllacloneMap.prototype._enableZFade = function () {
    if (!this._zFadeEnabled()) this._setZFadeStep(Z_FADE_STEP_DEFAULT_ON, true);
  };

  AllacloneMap.prototype._syncZFocusSelect = function () {
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    var sel = root ? root.querySelector('[data-map-control="z-focus"]') : null;
    if (!sel || this.focusFloorIndex < 0) return;
    sel.value = String(this.focusFloorIndex);
  };

  AllacloneMap.prototype._setFocusFloor = function (index, enableFade) {
    if (index < 0 || index >= this.mapFloors.length) return;
    var floor = this.mapFloors[index];
    this.focusFloorIndex = index;
    // Bloated Brewall floors (Vex Thal L0 spans ~160 Z): keep current focus if it sits
    // inside the floor so doors/detail near the camera stay; otherwise use mid.
    var span = floor.max - floor.min;
    if (span > Z_HEIGHT_SCALE * 4 && hasFiniteZ(this.focusZ) &&
        this.focusZ >= floor.min && this.focusZ <= floor.max) {
      // keep this.focusZ
    } else {
      this.focusZ = floor.mid;
    }
    this.focusLevelId = floor.kind === 'level' ? floor.id : null;
    this.focusSpawnIndex = this._nearestSpawnIndex(this.focusZ);
    this._syncZFocusSelect();
    if (enableFade) this._enableZFade();
    this._applyZFade();
  };

  AllacloneMap.prototype._stepFocusFloor = function (delta) {
    if (this.mapFloors.length < 2) return;
    var idx = this.focusFloorIndex;
    if (idx < 0) idx = 0;
    var next = (idx + delta + this.mapFloors.length) % this.mapFloors.length;
    this._setFocusFloor(next, true);
  };

  AllacloneMap.prototype._setFocusFromSpawn = function (index) {
    if (index < 0 || index >= this.spawnPoints.length) return;
    var pt = this.spawnPoints[index];
    if (!hasFiniteZ(pt.z)) return;
    this.focusSpawnIndex = index;
    this.focusZ = pt.z;
    var floorIdx = this._bestFloorIndexForSpawn(pt);
    if (floorIdx >= 0) {
      var floor = this.mapFloors[floorIdx];
      this.focusFloorIndex = floorIdx;
      this.focusLevelId = floor.kind === 'level' ? floor.id : null;
      // Prefer exact spawn Z for continuous fade when the floor is a Z-band fallback.
      if (floor.kind === 'z') this.focusLevelId = null;
      this._syncZFocusSelect();
    } else {
      this.focusFloorIndex = -1;
      this.focusLevelId = null;
    }
    this._enableZFade();
    this._applyZFade();
  };

  AllacloneMap.prototype._rebuildFloorControls = function () {
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    if (!root) return;
    var wrap = root.querySelector('[data-map-control-wrap="z-floor"]');
    var sel = root.querySelector('[data-map-control="z-focus"]');
    var fadeWrap = root.querySelector('[data-map-control-wrap="z-fade"]');
    var multi = this.mapFloors.length >= 2;
    if (wrap) {
      if (multi) wrap.removeAttribute('hidden');
      else wrap.setAttribute('hidden', 'hidden');
    }
    if (fadeWrap) fadeWrap.style.display = '';
    if (!sel) return;
    sel.innerHTML = '';
    for (var i = 0; i < this.mapFloors.length; i++) {
      var opt = document.createElement('option');
      opt.value = String(i);
      opt.textContent = floorLabel(this.mapFloors[i]);
      sel.appendChild(opt);
    }
    if (this.focusFloorIndex >= 0) sel.value = String(this.focusFloorIndex);
  };

  AllacloneMap.prototype._initFloorFocus = function () {
    this.mapFloors = finalizeMapFloors(this.levelStats, this.lineMids);
    var floorIdx = -1;
    // NPC/spawn pages: pick Brewall floor from spawn Z + XY (localized wings need both).
    if (this.focusSpawnIndex >= 0 && this.focusSpawnIndex < this.spawnPoints.length) {
      floorIdx = this._bestFloorIndexForSpawn(this.spawnPoints[this.focusSpawnIndex]);
    } else if (hasFiniteZ(this.focusZ)) {
      floorIdx = this._bestFloorIndexForZ(this.focusZ);
    }
    if (floorIdx < 0) {
      for (var i = 0; i < this.mapFloors.length; i++) {
        if (this.mapFloors[i].kind === 'level' && this.mapFloors[i].id === 0) {
          floorIdx = i;
          break;
        }
      }
    }
    // No L0 in the file: pick the lowest floor (list is ascending).
    if (floorIdx < 0 && this.mapFloors.length) floorIdx = 0;
    if (floorIdx >= 0) {
      var floor = this.mapFloors[floorIdx];
      this.focusFloorIndex = floorIdx;
      // Keep spawn Z when present (better continuous fade); else floor mid.
      if (!hasFiniteZ(this.focusZ)) this.focusZ = floor.mid;
      this.focusLevelId = floor.kind === 'level' ? floor.id : null;
    }
    this._rebuildFloorControls();
  };

  AllacloneMap.prototype._layerChecked = function (key) {
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    var el = root ? root.querySelector('[data-map-layer="' + key + '"]') : null;
    return el ? !!el.checked : true;
  };

  AllacloneMap.prototype._loadAll = function () {
    var self = this;
    var tasks = [];
    for (var i = 0; i < this.layerFiles.length; i++) {
      tasks.push(this._loadFile(this.layerFiles[i]));
    }
    Promise.all(tasks).then(function () {
      // Map file layers sit above the grid and below paths/spawns.
      self.gridLayer.moveToBottom();
      self.pathLayer.moveToTop();
      self.spawnLayer.moveToTop();
      self._drawGrid();
      self._drawPaths();
      self._drawRoamBoxes();
      self._drawSpawns();
      self._initFloorFocus();
      self._applyZFade();
      self._fitView();
    });
  };

  AllacloneMap.prototype._loadFile = function (entry) {
    var self = this;
    var url = this.mapBaseUrl + encodeURIComponent(entry.file);
    return fetch(url).then(function (res) {
      return res.ok ? res.text() : null;
    }).then(function (text) {
      if (!text) return;
      var parsed = parseMapContent(text);
      var layer = new Konva.Layer({ name: 'file-' + entry.key, listening: false });
      layer.visible(self._layerChecked(entry.key));
      self.fileLayers[entry.key] = layer;
      self.stage.add(layer);
      self._drawParsed(layer, parsed);
      for (var i = 0; i < parsed.lines.length; i++) {
        var p = parsed.lines[i].points;
        addToBounds(self.bounds, p[0], p[1]);
        addToBounds(self.bounds, p[2], p[3]);
      }
    }).catch(function () { /* a missing layer file just leaves that layer empty */ });
  };

  AllacloneMap.prototype._drawParsed = function (layer, parsed) {
    var i;
    for (i = 0; i < parsed.lines.length; i++) {
      var line = parsed.lines[i];
      this._recordLineLevel(line);
      var shape = new Konva.Line({
        points: line.points,
        stroke: line.color,
        strokeWidth: this.strokeWidth,
        lineCap: 'round',
        lineJoin: 'round'
      });
      shape.setAttr('isMapLine', true);
      shape.setAttr('mapZ0', line.z0);
      shape.setAttr('mapZ1', line.z1);
      shape.setAttr('mapMidX', (line.points[0] + line.points[2]) / 2);
      shape.setAttr('mapMidY', (line.points[1] + line.points[3]) / 2);
      shape.setAttr('mapLevelId', line.levelId);
      layer.add(shape);
    }
    for (i = 0; i < parsed.points.length; i++) {
      var pt = parsed.points[i];
      var circle = new Konva.Circle({ x: pt.x, y: pt.y, radius: 4, fill: pt.color });
      circle.setAttr('isMapLabel', true);
      circle.setAttr('mapZ', pt.z);
      circle.setAttr('mapX', pt.x);
      circle.setAttr('mapY', pt.y);
      layer.add(circle);
      var text = new Konva.Text({
        x: pt.x + 8,
        y: pt.y - 6,
        text: pt.label,
        fontSize: 12,
        fill: pt.color
      });
      text.setAttr('isMapLabel', true);
      text.setAttr('mapZ', pt.z);
      text.setAttr('mapX', pt.x);
      text.setAttr('mapY', pt.y);
      layer.add(text);
    }
  };

  /** Nearest Brewall color floor for an unleveled Z (doors/traps). */
  AllacloneMap.prototype._nearestFloorIndexForZ = function (z) {
    if (!hasFiniteZ(z) || !this.mapFloors.length) return -1;
    var best = -1;
    var bestScore = Infinity;
    for (var i = 0; i < this.mapFloors.length; i++) {
      var f = this.mapFloors[i];
      if (f.kind !== 'level') continue;
      var d = Math.abs(z - f.mid);
      var inRange = z >= f.min - Z_HEIGHT_SCALE && z <= f.max + Z_HEIGHT_SCALE;
      // Prefer floors whose Z band contains the sample; then closest median.
      var score = inRange ? d : d + 1e6;
      if (score < bestScore) {
        bestScore = score;
        best = i;
      }
    }
    return best;
  };

  /**
   * Non-floor-colored geometry (doors/traps/gray detail).
   * Discrete floor: assign to nearest Brewall floor by Z — upper-deck doors (Z≈124 on
   * Vex Thal green) fade on L0 instead of staying solid over faded walls.
   * Continuous autofade: opacity vs focusZ.
   */
  AllacloneMap.prototype._unleveledFadeOpacity = function (z0, z1, midX, midY) {
    var faded = this._zFadedAlpha();
    var zMid = (Number(z0) + Number(z1)) / 2;
    if (this.focusLevelId != null) {
      var idx = this._nearestFloorIndexForZ(zMid);
      if (idx >= 0 && this.mapFloors[idx].id !== this.focusLevelId) return faded;
      var floor = this.mapFloors[this.focusFloorIndex];
      if (floor && this._floorHasXy(floor) && hasFiniteZ(midX) && hasFiniteZ(midY) &&
          !this._floorXyContains(floor, midX, midY)) {
        return faded;
      }
      return 1;
    }
    return lineOpacity(z0, z1, this.focusZ, Z_HEIGHT_SCALE, faded);
  };

  AllacloneMap.prototype._lineFadeOpacity = function (z0, z1, levelId, midX, midY) {
    var focusZ = this.focusZ;
    var faded = this._zFadedAlpha();
    // Discrete Brewall floor (Zeal set_level mode).
    if (this.focusLevelId != null) {
      if (levelId === INVALID_LEVEL_ID) {
        return this._unleveledFadeOpacity(z0, z1, midX, midY);
      }
      return levelId === this.focusLevelId ? 1 : faded;
    }
    if (levelId === INVALID_LEVEL_ID) {
      return this._unleveledFadeOpacity(z0, z1, midX, midY);
    }
    return lineOpacity(z0, z1, focusZ, Z_HEIGHT_SCALE, faded);
  };

  AllacloneMap.prototype._labelFadeOpacity = function (z, x, y) {
    return this._unleveledFadeOpacity(z, z, x, y);
  };

  AllacloneMap.prototype._applyZFade = function () {
    var enabled = this._zFadeEnabled() && (hasFiniteZ(this.focusZ) || this.focusLevelId != null);
    var focusZ = this.focusZ;
    var key;
    for (key in this.fileLayers) {
      if (!Object.prototype.hasOwnProperty.call(this.fileLayers, key)) continue;
      var layer = this.fileLayers[key];
      var nodes = layer.getChildren();
      for (var i = 0; i < nodes.length; i++) {
        var node = nodes[i];
        if (node.getAttr('isMapLine')) {
          node.opacity(enabled
            ? this._lineFadeOpacity(
              node.getAttr('mapZ0'),
              node.getAttr('mapZ1'),
              node.getAttr('mapLevelId'),
              node.getAttr('mapMidX'),
              node.getAttr('mapMidY')
            )
            : 1);
        } else if (node.getAttr('isMapLabel')) {
          node.opacity(enabled
            ? this._labelFadeOpacity(node.getAttr('mapZ'), node.getAttr('mapX'), node.getAttr('mapY'))
            : 1);
        }
      }
    }

    var floor = (this.focusFloorIndex >= 0) ? this.mapFloors[this.focusFloorIndex] : null;
    for (var r = 0; r < this.spawnRings.length; r++) {
      var ring = this.spawnRings[r];
      var pt = this.spawnPoints[r];
      var focused = (r === this.focusSpawnIndex);
      var onLevel = true;
      if (enabled && hasFiniteZ(pt.z)) {
        if (floor && this.focusLevelId != null) {
          onLevel = this._spawnOnSelectedFloor(pt, floor);
        } else {
          onLevel = Math.abs(pt.z - focusZ) <= Z_HEIGHT_SCALE;
        }
      }
      ring.opacity(enabled && !onLevel ? SPAWN_OFFLEVEL_OPACITY : 1);
      ring.strokeWidth(focused ? 5 : 3);
      ring.stroke(focused ? '#c00000' : 'red');
    }
    for (var t = 0; t < this.spawnLabelNodes.length; t++) {
      var lab = this.spawnLabelNodes[t];
      var idx = lab.getAttr('spawnIndex');
      var spt = this.spawnPoints[idx];
      var labOn = true;
      if (enabled && spt && hasFiniteZ(spt.z)) {
        if (floor && this.focusLevelId != null) {
          labOn = this._spawnOnSelectedFloor(spt, floor);
        } else {
          labOn = Math.abs(spt.z - focusZ) <= Z_HEIGHT_SCALE;
        }
      }
      lab.opacity(enabled && !labOn ? SPAWN_OFFLEVEL_OPACITY : 1);
    }

    this.stage.batchDraw();
  };

  AllacloneMap.prototype._drawGrid = function () {
    this.gridLayer.destroyChildren();
    var gridSize = 200;
    var b = this.bounds;
    if (!boundsValid(b)) return;
    var color = 'rgba(0,0,0,0.15)';
    var x = Math.floor(b.minX / gridSize) * gridSize;
    for (; x <= b.maxX; x += gridSize) {
      this.gridLayer.add(new Konva.Line({ points: [x, b.minY, x, b.maxY], stroke: color, strokeWidth: 1 }));
    }
    var y = Math.floor(b.minY / gridSize) * gridSize;
    for (; y <= b.maxY; y += gridSize) {
      this.gridLayer.add(new Konva.Line({ points: [b.minX, y, b.maxX, y], stroke: color, strokeWidth: 1 }));
    }
  };

  AllacloneMap.prototype._drawPaths = function () {
    this.pathLayer.destroyChildren();
    var color = '#16E2F5';
    var groups = this.pathPoints;
    for (var key in groups) {
      if (!Object.prototype.hasOwnProperty.call(groups, key)) continue;
      var path = groups[key];
      for (var i = 1; i < path.length; i++) {
        var a = spawnToCanvas(path[i - 1]);
        var b = spawnToCanvas(path[i]);
        this.pathLayer.add(new Konva.Arrow({
          points: [a.x, a.y, b.x, b.y],
          stroke: color,
          fill: color,
          strokeWidth: 3,
          pointerLength: 12,
          pointerWidth: 12,
          dash: [20, 12]
        }));
      }
    }
  };

  AllacloneMap.prototype._drawRoamBoxes = function () {
    for (var i = 0; i < this.roamBoxes.length; i++) {
      var box = this.roamBoxes[i];
      // DB min/max are loc axes; convert corners to canvas
      var x0 = -Number(box.max_x);
      var y0 = -Number(box.max_y);
      var x1 = -Number(box.min_x);
      var y1 = -Number(box.min_y);
      this.pathLayer.add(new Konva.Rect({
        x: Math.min(x0, x1),
        y: Math.min(y0, y1),
        width: Math.abs(x1 - x0),
        height: Math.abs(y1 - y0),
        stroke: '#16E2F5',
        fill: 'rgba(22, 226, 245, 0.08)',
        strokeWidth: 4
      }));
    }
  };

  AllacloneMap.prototype._drawSpawns = function () {
    this.spawnLayer.destroyChildren();
    this.spawnRings = [];
    this.spawnLabelNodes = [];
    var self = this;
    for (var i = 0; i < this.spawnPoints.length; i++) {
      var pt = this.spawnPoints[i];
      var ring = new Konva.Ring({
        x: pt.x,
        y: pt.y,
        innerRadius: 8,
        outerRadius: 18,
        fill: 'yellow',
        stroke: 'red',
        strokeWidth: 3
      });
      ring.setAttr('spawnIndex', i);
      ring.on('click tap', function () {
        self._setFocusFromSpawn(this.getAttr('spawnIndex'));
      });
      ring.on('mouseenter', function () {
        self.container.style.cursor = 'pointer';
      });
      ring.on('mouseleave', function () {
        self.container.style.cursor = 'grab';
      });
      this.spawnLayer.add(ring);
      this.spawnRings.push(ring);
      if (pt.label) {
        var label = new Konva.Text({
          x: pt.x + 20,
          y: pt.y - 8,
          text: pt.label,
          fontSize: 14,
          fill: '#b00000'
        });
        label.setAttr('spawnIndex', i);
        this.spawnLayer.add(label);
        this.spawnLabelNodes.push(label);
      }
    }
    this._syncSpawnScreenScale(1);
    this.spawnAnim = new Konva.Animation(function (frame) {
      var pulse = Math.sin((frame.time * 2 * Math.PI) / 4000) * 0.35 + 1;
      self._syncSpawnScreenScale(pulse);
    }, this.spawnLayer);
  };

  AllacloneMap.prototype._setSpawnAnim = function (on) {
    if (!this.spawnAnim) return;
    if (on) {
      this.spawnAnim.start();
      return;
    }
    this.spawnAnim.stop();
    this._syncSpawnScreenScale(1);
    this.spawnLayer.batchDraw();
  };

  AllacloneMap.prototype._fitView = function () {
    var target = emptyBounds();
    if (this.fit === 'spawns' && this.spawnPoints.length > 0) {
      for (var i = 0; i < this.spawnPoints.length; i++) {
        addToBounds(target, this.spawnPoints[i].x, this.spawnPoints[i].y);
      }
      var cx = (target.minX + target.maxX) / 2;
      var cy = (target.minY + target.maxY) / 2;
      var half = MIN_SPAWN_FRAME / 2;
      addToBounds(target, cx - half, cy - half);
      addToBounds(target, cx + half, cy + half);
    } else if (boundsValid(this.bounds)) {
      target = this.bounds;
    }
    if (!boundsValid(target)) {
      this.stage.batchDraw();
      return;
    }
    var pad = 0.08;
    var w = (target.maxX - target.minX) * (1 + 2 * pad) || 1;
    var h = (target.maxY - target.minY) * (1 + 2 * pad) || 1;
    var scale = Math.min(this.stage.width() / w, this.stage.height() / h);
    scale = Math.max(MIN_SCALE, Math.min(MAX_SCALE, scale));
    var midX = (target.minX + target.maxX) / 2;
    var midY = (target.minY + target.maxY) / 2;
    this.stage.scale({ x: scale, y: scale });
    this.stage.position({
      x: this.stage.width() / 2 - midX * scale,
      y: this.stage.height() / 2 - midY * scale
    });
    this._syncSpawnScreenScale(1);
    this.stage.batchDraw();
  };

  AllacloneMap.prototype._applyStrokeWidth = function () {
    for (var key in this.fileLayers) {
      if (!Object.prototype.hasOwnProperty.call(this.fileLayers, key)) continue;
      var nodes = this.fileLayers[key].find('Line');
      for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].getAttr('isMapLine')) {
          nodes[i].strokeWidth(this.strokeWidth);
        }
      }
    }
    this.stage.batchDraw();
  };

  function bootFromConfig() {
    var configs = [];
    if (global.AllacloneMapConfigs && global.AllacloneMapConfigs.length) {
      configs = global.AllacloneMapConfigs;
    } else if (global.AllacloneMapConfig) {
      configs = [global.AllacloneMapConfig];
    }
    if (!configs.length) return;
    global.AllacloneMapInstances = [];
    for (var i = 0; i < configs.length; i++) {
      global.AllacloneMapInstances.push(new AllacloneMap(configs[i]));
    }
    global.AllacloneMapInstance = global.AllacloneMapInstances[0];
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFromConfig);
  } else {
    bootFromConfig();
  }

  global.AllacloneMap = AllacloneMap;
})(window);
