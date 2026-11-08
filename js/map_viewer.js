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
  var Z_FADED_ALPHA = 0.2;
  var SPAWN_OFFLEVEL_OPACITY = 0.35;

  function rgbCss(r, g, b) {
    return 'rgb(' + r + ',' + g + ',' + b + ')';
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
        lines.push({
          points: [parseFloat(parts[0]), parseFloat(parts[1]), parseFloat(parts[3]), parseFloat(parts[4])],
          z0: parseFloat(parts[2]),
          z1: parseFloat(parts[5]),
          color: rgbCss(parseInt(parts[6], 10) || 0, parseInt(parts[7], 10) || 0, parseInt(parts[8], 10) || 0)
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

  /** Quantize spawn Zs into clusters spaced by heightScale. */
  function buildZClusters(spawnPoints, scale) {
    var zs = [];
    var i;
    for (i = 0; i < spawnPoints.length; i++) {
      if (hasFiniteZ(spawnPoints[i].z)) zs.push(spawnPoints[i].z);
    }
    zs.sort(function (a, b) { return a - b; });
    var clusters = [];
    for (i = 0; i < zs.length; i++) {
      var z = zs[i];
      if (!clusters.length || Math.abs(z - clusters[clusters.length - 1].z) >= scale) {
        clusters.push({ z: z, count: 1 });
      } else {
        var c = clusters[clusters.length - 1];
        c.z = (c.z * c.count + z) / (c.count + 1);
        c.count += 1;
      }
    }
    return clusters;
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
    this.zClusters = buildZClusters(this.spawnPoints, Z_HEIGHT_SCALE);
    this.zFadeEnabled = !!this.opts.zFadeDefault;
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
    this.stage.batchDraw();
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
    if (zFade) {
      this.zFadeEnabled = !!zFade.checked;
      zFade.addEventListener('change', function () {
        self.zFadeEnabled = !!zFade.checked;
        self._applyZFade();
      });
    }

    var zFocus = root.querySelector('[data-map-control="z-focus"]');
    if (zFocus) {
      zFocus.addEventListener('change', function () {
        var val = parseFloat(zFocus.value);
        if (!isFinite(val)) return;
        self.focusZ = val;
        self.focusSpawnIndex = self._nearestSpawnIndex(val);
        self._applyZFade();
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

  AllacloneMap.prototype._syncZFocusSelect = function () {
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    var sel = root ? root.querySelector('[data-map-control="z-focus"]') : null;
    if (!sel || !hasFiniteZ(this.focusZ)) return;
    var bestIdx = -1;
    var bestDist = Infinity;
    for (var i = 0; i < sel.options.length; i++) {
      var oz = parseFloat(sel.options[i].value);
      if (!isFinite(oz)) continue;
      var d = Math.abs(oz - this.focusZ);
      if (d < bestDist) {
        bestDist = d;
        bestIdx = i;
      }
    }
    if (bestIdx >= 0) sel.selectedIndex = bestIdx;
  };

  AllacloneMap.prototype._setFocusFromSpawn = function (index) {
    if (index < 0 || index >= this.spawnPoints.length) return;
    var pt = this.spawnPoints[index];
    if (!hasFiniteZ(pt.z)) return;
    this.focusSpawnIndex = index;
    this.focusZ = pt.z;
    this._syncZFocusSelect();
    // Enable fade when the user picks a floor via spawn click.
    var root = document.getElementById(this.opts.controlsId || 'map-controls');
    var zFade = root ? root.querySelector('[data-map-control="z-fade"]') : null;
    if (zFade && !zFade.checked) {
      zFade.checked = true;
      this.zFadeEnabled = true;
    } else if (!zFade) {
      this.zFadeEnabled = true;
    }
    this._applyZFade();
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
      layer.add(shape);
    }
    for (i = 0; i < parsed.points.length; i++) {
      var pt = parsed.points[i];
      var circle = new Konva.Circle({ x: pt.x, y: pt.y, radius: 4, fill: pt.color });
      circle.setAttr('isMapLabel', true);
      circle.setAttr('mapZ', pt.z);
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
      layer.add(text);
    }
  };

  AllacloneMap.prototype._applyZFade = function () {
    var enabled = this.zFadeEnabled && hasFiniteZ(this.focusZ);
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
            ? lineOpacity(node.getAttr('mapZ0'), node.getAttr('mapZ1'), focusZ, Z_HEIGHT_SCALE, Z_FADED_ALPHA)
            : 1);
        } else if (node.getAttr('isMapLabel')) {
          node.opacity(enabled
            ? pointOpacity(node.getAttr('mapZ'), focusZ, Z_HEIGHT_SCALE, Z_FADED_ALPHA)
            : 1);
        }
      }
    }

    for (var r = 0; r < this.spawnRings.length; r++) {
      var ring = this.spawnRings[r];
      var pt = this.spawnPoints[r];
      var focused = (r === this.focusSpawnIndex);
      var onLevel = !enabled || !hasFiniteZ(pt.z) ||
        Math.abs(pt.z - focusZ) <= Z_HEIGHT_SCALE;
      ring.opacity(enabled && !onLevel ? SPAWN_OFFLEVEL_OPACITY : 1);
      ring.strokeWidth(focused ? 5 : 3);
      ring.stroke(focused ? '#c00000' : 'red');
    }
    for (var t = 0; t < this.spawnLabelNodes.length; t++) {
      var lab = this.spawnLabelNodes[t];
      var idx = lab.getAttr('spawnIndex');
      var spt = this.spawnPoints[idx];
      var labOn = !enabled || !spt || !hasFiniteZ(spt.z) ||
        Math.abs(spt.z - focusZ) <= Z_HEIGHT_SCALE;
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
    var rings = this.spawnRings;
    this.spawnAnim = new Konva.Animation(function (frame) {
      var scale = Math.sin((frame.time * 2 * Math.PI) / 4000) * 0.35 + 1;
      for (var r = 0; r < rings.length; r++) {
        rings[r].scale({ x: scale, y: scale });
      }
    }, this.spawnLayer);
  };

  AllacloneMap.prototype._setSpawnAnim = function (on) {
    if (!this.spawnAnim) return;
    if (on) {
      this.spawnAnim.start();
      return;
    }
    this.spawnAnim.stop();
    for (var r = 0; r < this.spawnRings.length; r++) {
      this.spawnRings[r].scale({ x: 1, y: 1 });
    }
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
    if (!global.AllacloneMapConfig) return;
    global.AllacloneMapInstance = new AllacloneMap(global.AllacloneMapConfig);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFromConfig);
  } else {
    bootFromConfig();
  }

  global.AllacloneMap = AllacloneMap;
})(window);
