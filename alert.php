<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSocket Test</title>
</head>
<body>
    <h1>Alertes</h1>
    <div id="alerts"></div>

    <script>
        // Se connecter au serveur WebSocket
        const socket = new WebSocket('ws://localhost:8080'); // L'adresse du WebSocket backend

        // Lorsque la connexion WebSocket est ouverte
        socket.onopen = () => {
            console.log('Connexion WebSocket ouverte');
        };

        // Lorsqu'un message est reçu du backend
        socket.onmessage = (event) => {
            const message = JSON.parse(event.data);  // Le message est au format JSON
            console.log('Message reçu:', message);
            
            // Ajouter l'alerte au frontend
            if (message.alert) {
                const alertsDiv = document.getElementById('alerts');
                const alertElement = document.createElement('div');
                alertElement.textContent = message.alert;  // Afficher l'alerte
                alertsDiv.appendChild(alertElement);
            }
        };

        // En cas d'erreur de connexion
        socket.onerror = (error) => {
            console.error('Erreur WebSocket:', error);
        };

        // Lors de la fermeture de la connexion WebSocket
        socket.onclose = () => {
            console.log('Connexion WebSocket fermée');
        };
    </script>
</body>
</html>
