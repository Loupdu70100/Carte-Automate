<?php
function getxmlstat($ip_address,$identifiant,$mdp) {
    // URL du fichier XML pour chaque IP
    $xml_url = "http://{$ip_address}:25001/status.xml?a={$identifiant}:{$mdp}";
    $xml = @simplexml_load_file($xml_url);

    // Valeurs par défaut si le XML ne peut pas être chargé
    $analogInput1 = 'N/A';
    $temperature1 = 'N/A';

    // Récupérer les valeurs du XML si disponible
    if ($xml !== false) {
        $analogInput1 = isset($xml->AnalogInput1) ? (string)$xml->AnalogInput1 : 'N/A';
        $temperature1 = isset($xml->Temperature1) ? (string)$xml->Temperature1 : 'N/A';
    }

    // Retourner les données en format JSON
    return [
        'analogInput1' => $analogInput1,
        'temperature1' => $temperature1
    ];
}

// Récupérer l'adresse IP à partir des paramètres de la requête
if (isset($_GET['ip_address'])&&isset($_GET['identifiant'])&&isset($_GET['mdp'])) {
    $ip_address = $_GET['ip_address'];
    $identifiant=$_GET['identifiant'];
    $mdp=$_GET['mdp'];

    $data = getxmlstat($ip_address,$identifiant,$mdp);
    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Adresse IP manquante']);
}
?>
