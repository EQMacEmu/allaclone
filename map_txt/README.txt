Interactive map geometry for allaclone (Brewall-format .txt map files).

These files are NOT committed (third-party cartography). Place zone files here
named by zone short name: <short>.txt for the map lines, <short>_1.txt for place
labels, <short>_2.txt etc. for extra layers. Filename case does not matter
(Qeynos_1.txt and qeynos_1.txt both work).

How to obtain and install
-------------------------
1. Download Brewall map packs from https://www.eqmaps.info/eq-map-files/
   (or copy from any EverQuest client map folder in the same text format).
   The Zeal checkout path Zeal/zone_map_src/map_files is also a valid source
   when redistribution rights for that pack are already established locally.
2. Copy the zone files you need into this map_txt/ directory (repo root).
   Example for North Qeynos: qeynos.txt (or Qeynos.txt) plus qeynos_1.txt labels.
3. Do not commit the geometry into git unless licensing is cleared for the fork.

Validate
--------
- Open zone.php?name=qeynos — interactive map appears when the base .txt exists.
- Drag to pan; scroll (or Ctrl+scroll) to zoom; Floor control when multi-level.
- Hover/readout shows /loc order (Y, X).
- If map_txt/ is empty or the zone file is missing, the interactive viewer is
  omitted; static maps/<short>.{jpg,gif,png} links on the zone page still work.
- Malformed lines are skipped by the viewer; a missing layer fetch leaves that
  layer empty without breaking the page.

Credit: Brewall (eqmaps.info); label layers may come from Project Miragul.
Keep third-party geometry separate unless redistribution permissions are clear.
