<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $nom = $_POST['nom'];
    $ip_adress = $_POST['ip_adress'];
    $adresse = $_POST['address']; // L'adresse saisie par l'utilisateur
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ubmap";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insertion dans la base de données
    $sql = "INSERT INTO points (nom, adresse_postale, latitude, longitude, ip_address) 
            VALUES ('$nom', '$adresse', '$latitude', '$longitude', '$ip_adress')";

    if ($conn->query($sql) === TRUE) {
        echo "Nouveau point ajouté avec succès";
        header("Location: ./index.php");
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
