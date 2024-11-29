<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Si une requête de recherche est reçue via AJAX
if (isset($_GET['ajax']) && isset($_GET['query'])) {
    $inputValue = $_GET['query'];
    
    // Préparation de la requête SQL pour rechercher les points correspondants
    $recherche = "SELECT * FROM points WHERE nom LIKE ? OR adresse_postale LIKE ? OR ip_address LIKE ?";
    $stmt = $conn->prepare($recherche);
    $searchTerm = "%$inputValue%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result2 = $stmt->get_result();

    // Récupération des résultats et stockage dans un tableau
    $points = [];
    if ($result2->num_rows > 0) {
        while ($row2 = $result2->fetch_assoc()) {
            $points[] = [
                'id' => $row['id'],
                'nom' => $row2['nom'],
                'adresse_postale' => $row2['adresse_postale'],
                'ip_address' => $row2['ip_address'],
                'latitude' => $row2['latitude'],
                'longitude' => $row2['longitude'],
            ];
        }
    }
    echo json_encode($points); // Retour des résultats en JSON pour l'appel AJAX
    exit;
}


// Requête SQL pour récupérer les points
$sql = "SELECT * FROM points";
$result = $conn->query($sql);

$points = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Ajouter les informations au tableau
        $points[] = [
            'id' => $row['id'],
            'nom' => $row['nom'],
            'adresse_postale' => $row['adresse_postale'],
            'ip_address' => $row['ip_address'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
        ];
    }
} else {
    echo "0 results";
}





$conn->close();



?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Interactive</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="./asset/style.css">
</head>
<body>
    <div id="map"></div>
<!--    <input type="text" id="search" name="search" placeholder="Recherchez ici..." />
    <button id="add">Ajouter Une UrbanBox</button>
--> 

    <!-- Bouton pour ouvrir la sidebar -->
    <button id="toggleSidebar">☰</button>

    <!-- Barre de recherche -->
    <div id="searchContainer">
        <input type="text" id="search" placeholder="Rechercher une UrbanBox..." autocomplete="off">
        <ul id="searchResults"></ul> <!-- Liste des résultats de recherche -->
    </div>
    <!-- Sidebar cachée par défaut -->
    <div id="sidebar">
        <h2>Actions</h2>
        <ul>
            <li><a href="javascript:void(0)" id="add">Ajouter</a></li>
            <li><a href="./alert.php">Alerter</a></li>
            <li><a href="#">Modifier</a></li>
        </ul>
    </div>


    <!-- Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span> <!-- Le bouton de fermeture -->
            <h2>Formulaire d'ajout Urbanbox</h2>
            <form id="contactForm" method="post" action="insertion.php">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required>

                <label for="ip_adress">IP :</label>
                <input type="text" id="ip_adress" name="ip_adress" required>

                <!-- Champ d'adresse -->
                <label for="address">Adresse :</label>
                <input type="text" id="address" name="address" placeholder="Entrez une adresse en France" autocomplete="off" required>
                <ul id="suggestions"></ul>

                <!-- Coordonnées géographiques : champ caché -->
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">

                <div id="result">
                    <h3>Coordonnées géographiques :</h3>
                    <p id="coordinates">Latitude : - <br> Longitude : -</p>
                </div>

                <button type="submit">Envoyer</button>
            </form>

            <!-- Message d'erreur en cas de validation échouée -->
            <div id="error-message" class="error-message"></div>
        </div>
    </div>

    <div id="overlay"></div>
<script>
    
    // Création de la carte
    var map = L.map('map', { zoomControl: false }).setView([47.096279, 6.495750], 5);

        // Couche de tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

// Récupération des points de la base de données en PHP
const points = <?php echo json_encode($points); ?>;
const markersMap = {};
// Ajouter les marqueurs sur la carte
points.forEach(function(point) {
    const marker = L.marker([point.latitude, point.longitude]).addTo(map);
    markersMap[point.ip_address] = marker;

    // Charger les données XML depuis l'API quand on clique sur le marqueur
    marker.on('click', function() {
        fetch(`xml.php?ip_address=${point.ip_address}`)
            .then(response => response.json())
            .then(data => {
                // Mettre à jour le popup avec les données récupérées
                marker.bindPopup(`
                    <b>${point.nom}</b><br>
                    Adresse IP : ${point.ip_address}<br>
                    Tension Batterie : ${data.analogInput1}<br>
                    Température Batterie : ${data.temperature1}<br>
                    <a href="http://${point.ip_address}:25000">Jet1Oeil Server</a><br>
                    <a href="http://${point.ip_address}:25001">Automate</a><br>
                    <a href="http://${point.ip_address}:80">Routeur</a><br>
                    <a href="./graphique.php?id_point=${point.id}">Graphique</a><br>
                `).openPopup();

            })
            .catch(error => {
                console.error('Erreur de chargement des données XML:', error);
            });
    });
});


        // Variables pour stocker latitude et longitude
        let latitude = null;
        let longitude = null;

        // Variables de la modal et du formulaire
        const modal = document.getElementById("myModal");
        const closeBtn = document.querySelector(".close");
        const addressInput = document.getElementById("address");
        const suggestionsList = document.getElementById("suggestions");
        const resultDiv = document.getElementById("result");
        const coordinatesText = document.getElementById("coordinates");
                // Récupère le bouton et la sidebar
        const toggleSidebarButton = document.getElementById("toggleSidebar");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");

        // Fonction pour ouvrir la modal
        const openModalBtn = document.getElementById("add");
        openModalBtn.onclick = function() {
            sidebar.style.left = "-250px";// Ferme la sidebar
            modal.style.display = "block";
            
            
        }

        // Fonction pour fermer la modal
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Gère le clic en dehors de la sidebar pour la fermer
        window.onclick = function(event) {
            // Vérifie que le clic n'est pas sur la sidebar ni sur le bouton d'ouverture
            if (event.target !== sidebar && event.target !== toggleSidebarButton && !sidebar.contains(event.target)) {
                sidebar.style.left = "-250px";// Ferme la sidebar
                overlay.style.display = "none"; 
            }
            if (event.target === modal) {
                modal.style.display = "none";
            }
        };


        // Recherche d'adresse avec adresse.data.gouv.fr (seulement en France)
        addressInput.addEventListener("input", function () {
            const query = addressInput.value;

            if (query.length < 3) {
                suggestionsList.style.display = 'none';
                return;
            }

            // Requête à l'API adresse.data.gouv.fr pour rechercher l'adresse
            fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`)
                .then(response => response.json())
                .then(data => {
                    suggestionsList.innerHTML = '';
                    if (data.features.length > 0) {
                        suggestionsList.style.display = 'block';
                        data.features.forEach(item => {
                            const listItem = document.createElement('li');
                            listItem.textContent = item.properties.label;
                            listItem.addEventListener('click', () => selectAddress(item));
                            suggestionsList.appendChild(listItem);
                        });
                    } else {
                        suggestionsList.style.display = 'none';
                    }
                })
                .catch(error => console.error('Erreur lors de la recherche d\'adresse:', error));
        });

        // Fonction pour sélectionner une adresse
        function selectAddress(item) {
            addressInput.value = item.properties.label;
            suggestionsList.style.display = 'none';

            // Extraire les coordonnées et les afficher
            latitude = item.geometry.coordinates[1];
            longitude = item.geometry.coordinates[0];

            // Afficher les coordonnées dans la section 'result'
            coordinatesText.innerHTML = `Latitude : ${latitude} <br> Longitude : ${longitude}`;
            resultDiv.style.display = 'block';

            // Remplir les champs cachés avec les coordonnées
            document.getElementById('latitude').value = latitude;
            document.getElementById('longitude').value = longitude;
        }

        // Validation du formulaire avant soumission
        document.getElementById("contactForm").onsubmit = function(event) {
            if (latitude === null || longitude === null) {
                event.preventDefault();  // Empêcher la soumission si les coordonnées ne sont pas définies
                alert("Veuillez sélectionner une adresse valide avec des coordonnées.");
            }
        }

        


        toggleSidebarButton.onclick = function() {
        if (sidebar.style.left === "0px") {
            sidebar.style.left = "-250px"; // Ferme la sidebar
            overlay.style.display = "none"; // Cache l'overlay
        } else {
            sidebar.style.left = "0px"; // Ouvre la sidebar
            overlay.style.display = "block"; // Affiche l'overlay
        }
    };


    document.getElementById('search').addEventListener('input', function() {
            const query = this.value;
            if (query.length < 3) {
                document.getElementById('searchResults').style.display = 'none';
                return;
            }

            // Appel AJAX à `index.php` avec le paramètre `query`
            fetch(`index.php?ajax=1&query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const searchResults = document.getElementById('searchResults');
                    searchResults.innerHTML = ''; // Nettoyer les anciens résultats
                    searchResults.style.display = 'block';

                    if (data.length === 0) {
                        searchResults.innerHTML = '<li>Aucun résultat trouvé</li>';
                        return;
                    }

                    // Ajouter les nouveaux résultats
                    data.forEach(point => {
                        const listItem = document.createElement('li');
                        listItem.textContent = `${point.nom} - ${point.adresse_postale}`;
                        listItem.addEventListener('click', () => {
                            // Centrer la carte sur le point sélectionné
                            map.setView([point.latitude, point.longitude], 12);
                            searchResults.style.display = 'none'; // Cacher les résultats après sélection
                    // Ouvrir le popup du marqueur depuis `markersMap` sans boucle
                    
                    const marker = markersMap[point.ip_address];
                    if (marker) {
                        fetch(`xml.php?ip_address=${point.ip_address}`)
                        .then(response => response.json())
                        .then(data => {
                        // Mettre à jour le popup avec les données récupérées
                        marker.bindPopup(`
                            <b>${point.nom}</b><br>
                            Adresse IP : ${point.ip_address}<br>
                            Tension Batterie : ${data.analogInput1}<br>
                            Température Batterie : ${data.temperature1}<br>
                            <a href="http://${point.ip_address}:25000">Jet1Oeil Server</a><br>
                            <a href="http://${point.ip_address}:25001">Automate</a><br>
                            <a href="http://${point.ip_address}:80">Routeur</a><br>
                            <a href="./graphique.php?id_point=${point.id}">Graphique</a><br>
                        `).openPopup();

            })
            .catch(error => {
                console.error('Erreur de chargement des données XML:', error);
            });
                    } else {
                        console.warn("Aucun marqueur trouvé pour l'IP:", point.ip_address);
                    }

                        });
                        searchResults.appendChild(listItem);
                    });
                })
                .catch(error => console.error('Erreur lors de la recherche:', error));
        });

    </script>
</body>
</html>
