<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération des données du formulaire
    $nom = $_POST['nom'];
    $ip_adress = $_POST['ip_adress'];
    $adresse = $_POST['address']; // L'adresse saisie par l'utilisateur
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $identifiant = $_POST['identifiant'];
    $mdp= $_POST['mdp'];

    // Connexion à la base de données
    $servername = "localhost";
    $username = "root";
    $password = "Zx23-Zx81";
    $dbname = "ubmap";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insertion dans la base de données
    $sql = "INSERT INTO points (nom, adresse_postale, latitude, longitude, ip_address, identifiant, mdp) 
            VALUES ('$nom', '$adresse', '$latitude', '$longitude', '$ip_adress', '$identifiant', '$mdp')";

    if ($conn->query($sql) === TRUE) {
        echo "Nouveau point ajouté avec succès";
        header("Location: ./index.php");
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
