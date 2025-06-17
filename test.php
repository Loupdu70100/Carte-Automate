<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Carte avec mini-carte cliquable pour switch thème</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  html, body, #map {
    margin: 0; padding: 0; height: 100%;
  }
  #mini-map {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 80px;
    height: 80px;
    border: 2px solid #000;
    border-radius: 8px;
    z-index: 1000;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    cursor: pointer; /* montre que c'est cliquable */
  }
</style>
</head>
<body>

<div id="map"></div>
<div id="mini-map" title="Clique pour changer le thème"></div>

<script>
  // Carte principale
  const map = L.map('map').setView([48.8566, 2.3522], 13);

  // Couches principales
  const osmLayerMain = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  });

  const satelliteLayerMain = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_satellite/{z}/{x}/{y}{r}.jpg', {
    maxZoom: 19,
    attribution: '&copy; Stadia Maps'
  });

  osmLayerMain.addTo(map);
  let mainIsOSM = true;
  syncMiniMap();

  // Mini carte
  const miniMap = L.map('mini-map', {
    zoomControl: false,
    attributionControl: false,
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    boxZoom: false,
    keyboard: false,
    inertia: false,
  }).setView(map.getCenter(), map.getZoom());

  // Couches mini
  const osmLayerMini = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
  const satelliteLayerMini = L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_satellite/{z}/{x}/{y}{r}.jpg', { maxZoom: 19 });

  // Par défaut la mini-carte montre le fond OPPOSÉ
  satelliteLayerMini.addTo(miniMap);
  let miniShowingSat = true;

  // Synchronisation mini-carte avec la principale (centre et zoom)
function syncMiniMap() {
  const center = map.getCenter();
  const zoom = map.getZoom();
  // On baisse le zoom de la mini carte pour voir plus large
  const miniZoom = Math.max(zoom - 3, 0);
  miniMap.setView(center, miniZoom);
}


  map.on('move', syncMiniMap);
  map.on('zoom', syncMiniMap);

  // Quand on clique sur la mini-carte : on inverse les couches
  miniMap.getContainer().addEventListener('click', () => {
    if (mainIsOSM) {
      // Carte principale en satellite
      map.removeLayer(osmLayerMain);
      satelliteLayerMain.addTo(map);
      mainIsOSM = false;

      // Mini en plan
      if (miniShowingSat) miniMap.removeLayer(satelliteLayerMini);
      osmLayerMini.addTo(miniMap);
      miniShowingSat = false;

    } else {
      // Carte principale en plan
      map.removeLayer(satelliteLayerMain);
      osmLayerMain.addTo(map);
      mainIsOSM = true;

      // Mini en satellite
      if (!miniShowingSat) miniMap.removeLayer(osmLayerMini);
      satelliteLayerMini.addTo(miniMap);
      miniShowingSat = true;
    }
  });

</script>

</body>
</html>
