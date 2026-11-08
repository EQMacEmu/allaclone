/**
 * Allaclone Brewall map viewer (Konva).
 * Map-file coords are Brewall model space: canvas = (-spawn2.x, -spawn2.y).
 * Mouseover /loc: X = -canvasX, Y = -canvasY.
 */
(function (global) {
  'use strict';

  var INVALID_LEVEL = -128;
  var LEVEL_LUT = {
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

  function levelFromRgb(r, g, b) {
    var key = r + ',' + g + ',' + b;
    return Object.prototype.hasOwnProperty.call(LEVEL_LUT, key) ? LEVEL_LUT[key] : INVALID_LEVEL;
  }

  function rgbCss(r, g, b) {
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  function parseMapContent(content) {
    var lines = [];
    var points = [];
    var levelsSeen = {};
    var rows = content.split(/\r?\n/);
    for (var i = 0; i < rows.length; i++) {
      var raw = rows[i].trim();
      if (!raw) continue;
      var type = raw.charAt(0);
      if (type !== 'L' && type !== 'P') continue;
      var parts = raw.substring(1).split(',');
      if (type === 'L' && parts.length >= 9) {
        var x0 = parseFloat(parts[0]);
        var y0 = parseFloat(parts[1]);
        var z0 = parseFloat(parts[2]);
        var x1 = parseFloat(parts[3]);
        var y1 = parseFloat(parts[4]);
        var z1 = parseFloat(parts[5]);
        var r = parseInt(parts[6], 10) || 0;
        var g = parseInt(parts[7], 10) || 0;
        var b = parseInt(parts[8], 10) || 0;
        var levelId = levelFromRgb(r, g, b);
        levelsSeen[levelId] = true;
        lines.push({
          points: [x0, y0, x1, y1],
          z0: z0,
          z1: z1,
          color: rgbCss(r, g, b),
          levelId: levelId
        });
      } else if (type === 'P' && parts.length >= 8) {
        var px = parseFloat(parts[0]);
        var py = parseFloat(parts[1]);
        var pr = parseInt(parts[3], 10) || 0;
        var pg = parseInt(parts[4], 10) || 0;
        var pb = parseInt(parts[5], 10) || 0;
        var label = parts.slice(7).join(',').trim().replace(/_/g, ' ');
        points.push({
          x: px,
          y: py,
          color: rgbCss(pr, pg, pb),
          label: label
        });
      }
    }
    return { lines: lines, points: points, levelsSeen: levelsSeen };
  }

  function spawnToCanvas(spawn) {
    // spawn.x / spawn.y are spawn2 (loc X / loc Y)
    return { x: -Number(spawn.x), y: -Number(spawn.y), z: spawn.z, label: spawn.label || '' };
  }

  function AllacloneMap(options) {
    this.opts = options || {};
    this.container = document.getElementById(this.opts.containerId || 'konva-container');
    if (!this.container || typeof Konva === 'undefined') {
      return;
    }
    this.mapBaseUrl = this.opts.mapBaseUrl || 'map_txt/';
    this.mapBaseName = this.opts.mapBaseName || '';
    this.layerFiles = this.opts.layerFiles || [''];
    this.spawnPoints = (this.opts.spawnPoints || []).map(spawnToCanvas);
    this.pathPoints = this.opts.pathPoints || {};
    this.roamBoxes = this.opts.roamBoxes || [];
    this.strokeWidth = 2;
    this.showPois = false;
    this.animateSpawns = false;
    this.floorFilter = 'all'; // 'all' | level id number
    this.fileLayers = {};
    this.spawnAnims = [];
    this.bounds = { minX: 0, minY: 0, maxX: 0, maxY: 0 };
    this._initStage();
    this._bindControls();
    this._loadAll();
  }

  AllacloneMap.prototype._initStage = function () {
    var w = this.container.clientWidth || 640;
    var h = this.container.clientHeight || 480;
    this.stage = new Konva.Stage({
      container: this.container,
      width: w,
      height: h,
      draggable: true
    });
    this.stage.scale({ x: 0.45, y: 0.45 });

    this.gridLayer = new Konva.Layer({ name: 'grid' });
    this.baseLayer = new Konva.Layer({ name: 'base' });
    this.poiLayers = {};
    this.spawnLayer = new Konva.Layer({ name: 'spawns' });
    this.pathLayer = new Konva.Layer({ name: 'paths' });

    this.stage.add(this.gridLayer);
    this.stage.add(this.baseLayer);
    this.stage.add(this.pathLayer);
    this.stage.add(this.spawnLayer);

    this._coordEl = document.createElement('div');
    this._coordEl.className = 'map-coord-display';
    this.container.appendChild(this._coordEl);

    this._creditEl = document.createElement('div');
    this._creditEl.className = 'map-credit';
    this._creditEl.innerHTML = 'Map geometry: <a href="https://www.eqmaps.info/" target="_blank" rel="noopener">Brewall</a>';
    this.container.appendChild(this._creditEl);

    var self = this;
    this.stage.on('mousemove', function () {
      var pos = self.stage.getPointerPosition();
      if (!pos) return;
      var transform = self.stage.getAbsoluteTransform().copy().invert();
      var mapPos = transform.point(pos);
      var locX = -mapPos.x;
      var locY = -mapPos.y;
      self._coordEl.textContent = 'Y: ' + locY.toFixed(1) + ', X: ' + locX.toFixed(1);
    });

    this.container.addEventListener('wheel', function (e) {
      e.preventDefault();
      var oldScale = self.stage.scaleX();
      var pointer = self.stage.getPointerPosition();
      if (!pointer) return;
      var scaleBy = e.deltaY > 0 ? 0.9 : 1.1;
      var newScale = oldScale * scaleBy;
      if (newScale < 0.05 || newScale > 8) return;
      var mousePointTo = {
        x: (pointer.x - self.stage.x()) / oldScale,
        y: (pointer.y - self.stage.y()) / oldScale
      };
      self.stage.scale({ x: newScale, y: newScale });
      self.stage.position({
        x: pointer.x - mousePointTo.x * newScale,
        y: pointer.y - mousePointTo.y * newScale
      });
    }, { passive: false });
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
      });
    }

    var poiToggle = root.querySelector('[data-map-control="show-pois"]');
    if (poiToggle) {
      poiToggle.addEventListener('change', function () {
        self.showPois = !!poiToggle.checked;
        self._applyPoiVisibility();
      });
    }

    var animToggle = root.querySelector('[data-map-control="animate"]');
    if (animToggle) {
      animToggle.addEventListener('change', function () {
        self.animateSpawns = !!animToggle.checked;
        self._applySpawnAnim();
      });
    }

    var floorSelect = root.querySelector('[data-map-control="floor"]');
    if (floorSelect) {
      floorSelect.addEventListener('change', function () {
        self.floorFilter = floorSelect.value;
        self._applyFloorFilter();
      });
    }

    var fileToggles = root.querySelectorAll('[data-map-layer]');
    for (var i = 0; i < fileToggles.length; i++) {
      (function (el) {
        el.addEventListener('change', function () {
          var key = el.getAttribute('data-map-layer');
          var layer = key === 'base' ? self.baseLayer : self.fileLayers[key];
          if (layer) {
            layer.visible(!!el.checked);
            self.stage.batchDraw();
          }
        });
      })(fileToggles[i]);
    }
  };

  AllacloneMap.prototype._layerKey = function (suffix) {
    return suffix === '' || suffix === null || suffix === undefined ? 'base' : String(suffix);
  };

  AllacloneMap.prototype._ensureFileLayer = function (key) {
    if (this.fileLayers[key]) return this.fileLayers[key];
    var layer = new Konva.Layer({ name: 'file-' + key });
    this.fileLayers[key] = layer;
    // Insert before path/spawn so overlays stay on top
    this.stage.add(layer);
    this.pathLayer.moveToTop();
    this.spawnLayer.moveToTop();
    return layer;
  };

  AllacloneMap.prototype._loadAll = function () {
    var self = this;
    var tasks = [];
    for (var i = 0; i < this.layerFiles.length; i++) {
      tasks.push(this._loadFile(this.layerFiles[i]));
    }
    Promise.all(tasks).then(function () {
      self._drawGrid();
      self._drawPaths();
      self._drawRoamBoxes();
      self._drawSpawns();
      self._centerView();
      self._populateFloorSelect();
      self._applyFloorFilter();
      self._applyPoiVisibility();
      self.stage.batchDraw();
    });
  };

  AllacloneMap.prototype._fileUrl = function (suffix) {
    var name = this.mapBaseName;
    if (suffix === '' || suffix === 0 || suffix === '0' || suffix === 'base') {
      return this.mapBaseUrl + name + '.txt';
    }
    var s = String(suffix).replace(/^_/, '');
    return this.mapBaseUrl + name + '_' + s + '.txt';
  };

  AllacloneMap.prototype._loadFile = function (suffix) {
    var self = this;
    var key = this._layerKey(suffix);
    var url = this._fileUrl(suffix);
    return fetch(url).then(function (res) {
      if (!res.ok) return null;
      return res.text();
    }).then(function (text) {
      if (!text) return;
      var parsed = parseMapContent(text);
      var layer = key === 'base' ? self.baseLayer : self._ensureFileLayer(key);
      if (key !== 'base') {
        self.poiLayers[key] = layer;
      }
      self._drawParsed(layer, parsed, key === 'base');
      self._expandBounds(parsed);
    }).catch(function () { /* missing layer ok */ });
  };

  AllacloneMap.prototype._expandBounds = function (parsed) {
    var b = this.bounds;
    var first = b.minX === 0 && b.maxX === 0 && b.minY === 0 && b.maxY === 0;
    function consider(x, y) {
      if (first) {
        b.minX = b.maxX = x;
        b.minY = b.maxY = y;
        first = false;
        return;
      }
      if (x < b.minX) b.minX = x;
      if (x > b.maxX) b.maxX = x;
      if (y < b.minY) b.minY = y;
      if (y > b.maxY) b.maxY = y;
    }
    var i;
    for (i = 0; i < parsed.lines.length; i++) {
      var p = parsed.lines[i].points;
      consider(p[0], p[1]);
      consider(p[2], p[3]);
    }
    for (i = 0; i < parsed.points.length; i++) {
      consider(parsed.points[i].x, parsed.points[i].y);
    }
  };

  AllacloneMap.prototype._drawParsed = function (layer, parsed, isBase) {
    var i;
    for (i = 0; i < parsed.lines.length; i++) {
      var line = parsed.lines[i];
      var shape = new Konva.Line({
        points: line.points,
        stroke: line.color,
        strokeWidth: this.strokeWidth,
        lineCap: 'round',
        lineJoin: 'round',
        listening: false
      });
      shape.setAttr('levelId', line.levelId);
      shape.setAttr('isMapLine', true);
      layer.add(shape);
    }
    for (i = 0; i < parsed.points.length; i++) {
      var pt = parsed.points[i];
      var circle = new Konva.Circle({
        x: pt.x,
        y: pt.y,
        radius: 4,
        fill: pt.color,
        visible: false
      });
      circle.setAttr('isPoi', true);
      layer.add(circle);
      var text = new Konva.Text({
        x: pt.x + 8,
        y: pt.y - 6,
        text: pt.label,
        fontSize: 12,
        fill: pt.color,
        visible: false
      });
      text.setAttr('isPoi', true);
      layer.add(text);
    }
    if (!isBase) {
      // POI file layers default visible for geometry, POI markers gated by showPois
    }
  };

  AllacloneMap.prototype._drawGrid = function () {
    this.gridLayer.destroyChildren();
    var gridSize = 200;
    var b = this.bounds;
    if (b.maxX === b.minX && b.maxY === b.minY) return;
    var color = 'rgba(0,0,0,0.15)';
    var x = Math.floor(b.minX / gridSize) * gridSize;
    for (; x <= b.maxX; x += gridSize) {
      this.gridLayer.add(new Konva.Line({
        points: [x, b.minY, x, b.maxY],
        stroke: color,
        strokeWidth: 1,
        listening: false
      }));
    }
    var y = Math.floor(b.minY / gridSize) * gridSize;
    for (; y <= b.maxY; y += gridSize) {
      this.gridLayer.add(new Konva.Line({
        points: [b.minX, y, b.maxX, y],
        stroke: color,
        strokeWidth: 1,
        listening: false
      }));
    }
    this.gridLayer.moveToBottom();
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
          dash: [20, 12],
          listening: false
        }));
      }
    }
  };

  AllacloneMap.prototype._drawRoamBoxes = function () {
    var boxes = this.roamBoxes;
    for (var i = 0; i < boxes.length; i++) {
      var box = boxes[i];
      // DB min/max are loc axes; convert corners to canvas
      var x0 = -Number(box.max_x);
      var y0 = -Number(box.max_y);
      var x1 = -Number(box.min_x);
      var y1 = -Number(box.min_y);
      var left = Math.min(x0, x1);
      var top = Math.min(y0, y1);
      var width = Math.abs(x1 - x0);
      var height = Math.abs(y1 - y0);
      this.pathLayer.add(new Konva.Rect({
        x: left,
        y: top,
        width: width,
        height: height,
        stroke: '#16E2F5',
        fill: 'rgba(22, 226, 245, 0.08)',
        strokeWidth: 4,
        listening: false
      }));
    }
  };

  AllacloneMap.prototype._drawSpawns = function () {
    this.spawnLayer.destroyChildren();
    this.spawnAnims = [];
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
      this.spawnLayer.add(ring);
      if (pt.label) {
        this.spawnLayer.add(new Konva.Text({
          x: pt.x + 20,
          y: pt.y - 8,
          text: pt.label,
          fontSize: 14,
          fill: '#b00000',
          listening: false
        }));
      }
      (function (target) {
        var a = new Konva.Animation(function (frame) {
          var scale = Math.sin((frame.time * 2 * Math.PI) / 4000) * 0.35 + 1;
          target.scale({ x: scale, y: scale });
        }, self.spawnLayer);
        self.spawnAnims.push(a);
      })(ring);
    }
  };

  AllacloneMap.prototype._centerView = function () {
    var focus = null;
    if (this.spawnPoints.length > 0) {
      focus = this.spawnPoints[0];
    } else {
      focus = {
        x: (this.bounds.minX + this.bounds.maxX) / 2,
        y: (this.bounds.minY + this.bounds.maxY) / 2
      };
    }
    var scale = this.stage.scaleX();
    this.stage.position({
      x: this.stage.width() / 2 - focus.x * scale,
      y: this.stage.height() / 2 - focus.y * scale
    });
  };

  AllacloneMap.prototype._applyStrokeWidth = function () {
    var layers = [this.baseLayer].concat(Object.keys(this.fileLayers).map(function (k) {
      return this.fileLayers[k];
    }, this));
    for (var li = 0; li < layers.length; li++) {
      var nodes = layers[li].find('Line');
      for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].getAttr('isMapLine')) {
          nodes[i].strokeWidth(this.strokeWidth);
        }
      }
    }
    this.stage.batchDraw();
  };

  AllacloneMap.prototype._applyPoiVisibility = function () {
    var show = this.showPois;
    var layers = [this.baseLayer].concat(Object.keys(this.poiLayers).map(function (k) {
      return this.poiLayers[k];
    }, this));
    // Also scan fileLayers for POI markers
    var all = [this.baseLayer];
    var key;
    for (key in this.fileLayers) {
      if (Object.prototype.hasOwnProperty.call(this.fileLayers, key)) {
        all.push(this.fileLayers[key]);
      }
    }
    for (var li = 0; li < all.length; li++) {
      var children = all[li].getChildren();
      for (var i = 0; i < children.length; i++) {
        if (children[i].getAttr('isPoi')) {
          children[i].visible(show);
        }
      }
    }
    this.stage.batchDraw();
  };

  AllacloneMap.prototype._applySpawnAnim = function () {
    for (var i = 0; i < this.spawnAnims.length; i++) {
      if (this.animateSpawns) this.spawnAnims[i].start();
      else {
        this.spawnAnims[i].stop();
        // reset scale
      }
    }
    if (!this.animateSpawns) {
      var rings = this.spawnLayer.find('Ring');
      for (var r = 0; r < rings.length; r++) {
        rings[r].scale({ x: 1, y: 1 });
      }
      this.spawnLayer.batchDraw();
    }
  };

  AllacloneMap.prototype._populateFloorSelect = function () {
    var sel = document.querySelector('#' + (this.opts.controlsId || 'map-controls') + ' [data-map-control="floor"]');
    if (!sel) return;
    var seen = {};
    var layers = [this.baseLayer];
    var key;
    for (key in this.fileLayers) {
      if (Object.prototype.hasOwnProperty.call(this.fileLayers, key)) {
        layers.push(this.fileLayers[key]);
      }
    }
    for (var li = 0; li < layers.length; li++) {
      var nodes = layers[li].find('Line');
      for (var i = 0; i < nodes.length; i++) {
        if (nodes[i].getAttr('isMapLine')) {
          var lvl = nodes[i].getAttr('levelId');
          if (lvl !== INVALID_LEVEL) seen[lvl] = true;
        }
      }
    }
    var ids = Object.keys(seen).map(Number).sort(function (a, b) { return b - a; });
    // Keep first "All floors" option; rebuild rest
    while (sel.options.length > 1) sel.remove(1);
    for (var j = 0; j < ids.length; j++) {
      var opt = document.createElement('option');
      opt.value = String(ids[j]);
      opt.textContent = 'Floor ' + ids[j];
      sel.appendChild(opt);
    }
    sel.disabled = ids.length <= 1;
  };

  AllacloneMap.prototype._applyFloorFilter = function () {
    var filter = this.floorFilter;
    var layers = [this.baseLayer];
    var key;
    for (key in this.fileLayers) {
      if (Object.prototype.hasOwnProperty.call(this.fileLayers, key)) {
        layers.push(this.fileLayers[key]);
      }
    }
    for (var li = 0; li < layers.length; li++) {
      var nodes = layers[li].find('Line');
      for (var i = 0; i < nodes.length; i++) {
        if (!nodes[i].getAttr('isMapLine')) continue;
        var lvl = nodes[i].getAttr('levelId');
        var show;
        if (filter === 'all') {
          show = true;
        } else {
          var want = parseInt(filter, 10);
          // Always show untagged + selected floor; fade others via opacity
          if (lvl === INVALID_LEVEL || lvl === want) {
            nodes[i].opacity(1);
            show = true;
          } else {
            nodes[i].opacity(0.12);
            show = true;
          }
        }
        if (filter === 'all') nodes[i].opacity(1);
        nodes[i].visible(show);
      }
    }
    this.stage.batchDraw();
  };

  function bootFromConfig() {
    if (!global.AllacloneMapConfig) return;
    var cfg = global.AllacloneMapConfig;
    global.AllacloneMapInstance = new AllacloneMap(cfg);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFromConfig);
  } else {
    bootFromConfig();
  }

  global.AllacloneMap = AllacloneMap;
})(window);
