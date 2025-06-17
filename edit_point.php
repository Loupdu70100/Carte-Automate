<?php
if (headers_sent($file, $line)) {
    die("Les headers ont déjà été envoyés dans $file à la ligne $line");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "Zx23-Zx81";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$point = null;

// Préparation d'une requête sécurisée
$stmt = $conn->prepare("SELECT * FROM points WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $point = [
        'id' => $row['id'],
        'nom' => $row['nom'],
        'model' => $row['model'],
        'adresse_postale' => $row['adresse_postale'],
        'ip_address' => $row['ip_address'],
        'port' => $row['port'],
        'latitude' => $row['latitude'],
        'longitude' => $row['longitude'],
        'identifiant' => $row['identifiant'],
        'mdp' => $row['mdp'],
        'last_ping_routeur' => $row['last_ping_routeur'],
    ];
} else {
    die("Aucun point trouvé avec l’ID donné.");
}

function modifierPoint($conn, $data) {
    $stmt = $conn->prepare("
        UPDATE points SET 
            nom = ?, 
            model = ?, 
            adresse_postale = ?, 
            ip_address = ?, 
            port = ?, 
            latitude = ?, 
            longitude = ?, 
            identifiant = ?, 
            mdp = ? 
        WHERE id = ?
    ");

    if (!$stmt) {
        die("Erreur préparation requête : " . $conn->error);
    }

    $stmt->bind_param(
        "ssssiddssi", // types : s = string, i = int, d = double
        $data['nom'],
        $data['model'],
        $data['adresse_postale'],
        $data['ip_address'],
        $data['port'],
        $data['latitude'],
        $data['longitude'],
        $data['identifiant'],
        $data['mdp'],
        $data['id']
    );

    if (!$stmt->execute()) {
        die("Erreur exécution : " . $stmt->error);
        return false;
    }

    $stmt->close();
    return true;
}
// Vérifie si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);

    // 1. Récupérer l'ancien point
    $stmt = $conn->prepare("SELECT * FROM points WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ancien = $result->fetch_assoc();
    $stmt->close();

    if (!$ancien) {
        die("❌ Point non trouvé.");
    }

    // 2. Fusionner POST avec l'ancien
    $data = [
        'id' => $id,
        'nom' => $_POST['nom'] ?? $ancien['nom'],
        'model' => $_POST['model'] ?? $ancien['model'],
        'adresse_postale' => $_POST['adresse_postale'] ?? $ancien['adresse_postale'],
        'ip_address' => $_POST['ip_address'] ?? $ancien['ip_address'],
        'port' => $_POST['port'] ?? $ancien['port'],
        'latitude' => $_POST['latitude'] ?? $ancien['latitude'],
        'longitude' => $_POST['longitude'] ?? $ancien['longitude'],
        'identifiant' => $_POST['identifiant'] ?? $ancien['identifiant'],
        'mdp' => $_POST['mdp'] ?? $ancien['mdp'],
    ];

    // 3. Appel de la fonction de mise à jour
    if (modifierPoint($conn, $data) == true){
        header("Location: index.php");
    }
    
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Modifier le Point</title>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        #map { height: 300px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-6">
        <h1 class="text-2xl font-bold mb-4 text-blue-600">Modifier le point : <?= htmlspecialchars($point['nom']) ?></h1>

        <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                ✅ Modifications enregistrées avec succès.
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $point['id'] ?>">

            <div>
                <label class="block text-sm font-medium">Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($point['nom']) ?>" class="w-full p-2 border rounded">
            </div>

            <div>
                <label class="block text-sm font-medium">Model</label>
                <input type="text" name="model" value="<?= htmlspecialchars($point['model']) ?>" class="w-full p-2 border rounded">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">IP</label>
                    <input type="text" name="ip_address" value="<?= $point['ip_address'] ?>" class="w-full p-2 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium">Port</label>
                    <input type="text" name="port" value="<?= $point['port'] ?>" class="w-full p-2 border rounded">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium">Identifiant</label>
                    <input type="text" name="identifiant" value="<?= $point['identifiant'] ?>" class="w-full p-2 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium">Mot de passe</label>
                    <input type="password" name="mdp" value="<?= $point['mdp'] ?>" class="w-full p-2 border rounded">
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mt-6">📍 Localisation</h3>
                <label class="inline-flex items-center mt-2">
                    <input type="radio" name="position_mode" value="adresse" checked class="mr-2">
                    Définir via adresse
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="position_mode" value="carte" class="mr-2">
                    Déplacer sur carte
                </label>
            </div>

            <div id="adresse_input" class="mt-2">
                <label class="block text-sm font-medium">Adresse</label>
                <input type="text" name="adresse_postale" value="<?= htmlspecialchars($point['adresse_postale']) ?>" class="w-full p-2 border rounded">
            </div>

            <div id="map" class="rounded mt-4" style="height:300px; display:none;"></div>

            <input type="hidden" name="latitude" id="latitude" value="<?= $point['latitude'] ?>">
            <input type="hidden" name="longitude" id="longitude" value="<?= $point['longitude'] ?>">

            <button type="submit" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                💾 Enregistrer
            </button>
        </form>
    </div>

    <script>
        const map = L.map('map').setView([<?= $point['latitude'] ?>, <?= $point['longitude'] ?>], 13);
        L.tileLayer('https://tiles.stadiamaps.com/tiles/alidade_satellite/{z}/{x}/{y}{r}.jpg', {
            maxZoom: 19
        }).addTo(map);

        let marker = L.marker([<?= $point['latitude'] ?>, <?= $point['longitude'] ?>], {draggable: true}).addTo(map);

        marker.on('dragend', function(e) {
            const position = marker.getLatLng();
            document.getElementById('latitude').value = position.lat;
            document.getElementById('longitude').value = position.lng;
        });

        // Gérer affichage carte vs adresse
        const modeRadios = document.querySelectorAll('input[name="position_mode"]');
        modeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                const mapDiv = document.getElementById('map');
                const adresseDiv = document.getElementById('adresse_input');
                if (radio.value === "carte") {
                    mapDiv.style.display = 'block';
                    adresseDiv.style.display = 'none';
                    map.invalidateSize(); // corrige l'affichage si caché au chargement
                } else {
                    mapDiv.style.display = 'none';
                    adresseDiv.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>
