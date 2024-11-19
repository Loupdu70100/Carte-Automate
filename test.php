<?php
include "xml.php";

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fonction pour tester si une IP est accessible via un port spécifique
function isIpAccessible($ip, $port = 80, $timeout = 0.1) {
    $connection = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if (is_resource($connection)) {
        fclose($connection);
        return true; // L'IP est accessible
    } else {
        return false; // L'IP n'est pas accessible
    }
}

// Insertion dans la base de données
$sql3 = "SELECT nom, ip_address FROM points";
$result3 = $conn->query($sql3);

if ($result3->num_rows > 0) {
    while($row3 = $result3->fetch_assoc()) {
        // Vérification de l'accessibilité de l'IP avant d'exécuter getxmlstat
        $ip_address = $row3['ip_address'];
        
        if (isIpAccessible($ip_address)) {
            // Si l'IP est accessible, on récupère les données XML
            $data = getxmlstat($ip_address);
            
            // Vérification si des données ont été récupérées
            if ($data) {
                // Affichage des données
                foreach ($data as $data1) {
                    echo($data1);
                }
            } else {
                echo "Aucune donnée récupérée pour l'IP $ip_address.<br>";
            }
        } else {
            // Si l'IP n'est pas accessible
            echo "L'IP $ip_address n'est pas accessible.<br>";
        }
    }
} else {
    echo "0 results";
}

$conn->close();
?>
