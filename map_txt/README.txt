Interactive map geometry for allaclone (Brewall-format .txt map files).

These zone files are committed with this fork so interactive maps work out of
the box. Files are named by zone short name: <short>.txt for the map lines,
<short>_1.txt for place labels, <short>_2.txt etc. for extra layers. Filename
case does not matter (Qeynos_1.txt and qeynos_1.txt both work).

Attribution
-----------
- Original cartography: Brewall (https://www.eqmaps.info/eq-map-files/)
- Pack used in this repository was downloaded from:
  https://github.com/CoastalRedwood/Zeal/tree/main/Zeal/zone_map_src/map_files
- Label layers may also include Project Miragul contributions present in that pack.

Validate
--------
- Open zone.php?name=qeynos — interactive map appears when the base .txt exists.
- Drag to pan; scroll (or Ctrl+scroll) to zoom; Floor control when multi-level.
- Hover/readout shows /loc order (Y, X).
- If a zone file is missing, the interactive viewer is omitted; static
  maps/<short>.{jpg,gif,png} links on the zone page still work.
- Malformed lines are skipped by the viewer; a missing layer fetch leaves that
  layer empty without breaking the page.
