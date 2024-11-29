<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervision des Alertes</title>
    <style>
        /* Les styles restent identiques */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 2rem;
            color: #444;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            max-width: 1200px;
            margin: 0 auto 20px auto;
        }

        .filters select, .filters input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        #alerts {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .alert:hover {
            background-color: #f9f9f9;
            transform: translateY(-2px);
        }

        .alert .info {
            flex: 1;
        }

        .alert .info p {
            margin: 5px 0;
        }

        .alert .info strong {
            color: #444;
        }

        .actions button {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .actions button.comment {
            background-color: #0984e3;
            color: white;
        }

        .actions button.comment:hover {
            background-color: #74b9ff;
        }

        .actions button.close {
            background-color: #d63031;
            color: white;
        }

        .actions button.close:hover {
            background-color: #e17055;
        }

        footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 0.9rem;
        }
        /* Style de la sidebar */
        #sidebar {

        position: fixed;
        left: -10px; /* Masque la sidebar en dehors de l'écran */
        top: 0;
        height: 100%;
        width: 250px;
        background-color: #f4f4f4;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.5);
        padding-top: 60px;
        z-index: 1002;
        transition: 0.3s; /* Animation pour la transition de la sidebar */
        }

        /* Style de la liste des actions dans la sidebar */
        #sidebar h2 {
        text-align: center;
        }

        #sidebar ul {
        list-style-type: none;
        padding: 0;
        }

        #sidebar ul li {
        padding: 15px 20px;
        text-align: left;
        }
        #sidebar ul li a {
        text-decoration: none;
        color: #000;
        font-size: 18px;
        }

        #sidebar ul li a:hover {
        padding: 10px 50px 10px 50px;
        border-radius: 10px;
        background-color: #D3D3D3;
        }
    </style>
</head>
<body>
    <h1>Supervision des Alertes</h1>
    <div id="sidebar">
        <h2>Actions</h2>
        <ul>
            <li><a href="./index.php">Accueil</a></li>
        </ul>
    </div>
    <div class="filters">
        <input type="text" placeholder="Rechercher une alerte..." id="search" />
        <select id="filter-status">
            <option value="all">Toutes les alertes</option>
            <option value="open">Ouvertes</option>
            <option value="closed">Clôturées</option>
        </select>
    </div>
    <div id="alerts">
        <!-- Les alertes générées par JS apparaîtront ici -->
    </div>
    <footer>
        <p>&copy; 2024 - Supervision des Alertes. Interface ergonomique et pensée pour les utilisateurs 💻.</p>
    </footer>

    <script>
        const apiUrl = 'http://localhost:3000/api/alerts'; // URL de l'API backend
        const searchInput = document.getElementById('search');
        const filterStatus = document.getElementById('filter-status');
        let alerts = []; // Stocke toutes les alertes localement

        async function loadAlerts() {
            try {
                const response = await fetch(apiUrl);
                alerts = await response.json(); // Sauvegarder dans une variable globale
                renderAlerts();
            } catch (error) {
                console.error('Erreur lors du chargement des alertes:', error);
            }
        }

        function renderAlerts() {
            const alertsDiv = document.getElementById('alerts');
            alertsDiv.innerHTML = '';

            const filterValue = filterStatus.value.toLowerCase();
            const searchValue = searchInput.value.toLowerCase();

            const filteredAlerts = alerts.filter(alert => {
                const matchesStatus = filterValue === 'all' || alert.status === filterValue;
                const matchesSearch = alert.motif.toLowerCase().includes(searchValue);
                return matchesStatus && matchesSearch;
            });

            filteredAlerts.forEach(alert => {
                const alertElement = document.createElement('div');
                alertElement.className = `alert`;
                alertElement.innerHTML = `
                    <div class="info">
                        <p><strong>Motif:</strong> ${alert.motif}</p>
                        <p><strong>Commentaire:</strong> ${alert.commentaire || 'Aucun'}</p>
                        <p><strong>Date:</strong> ${new Date(alert.datetime).toLocaleString()}</p>
                    </div>
                    <div class="actions">
                        <button class="comment" onclick="addComment(${alert.id})">Commentaire</button>
                        <button class="close" onclick="closeAlert(${alert.id})">Clôturer</button>
                    </div>
                `;
                alertsDiv.appendChild(alertElement);
            });
        }

        async function addComment(alertId) {
            const commentaire = prompt('Ajouter un commentaire :');
            if (commentaire) {
                try {
                    await fetch(`${apiUrl}/${alertId}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ commentaire }),
                    });
                    loadAlerts();
                } catch (error) {
                    console.error('Erreur lors de l\'ajout du commentaire:', error);
                }
            }
        }

        async function closeAlert(alertId) {
            if (confirm('Êtes-vous sûr de vouloir clôturer cette alerte ?')) {
                try {
                    await fetch(`${apiUrl}/${alertId}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'closed' }),
                    });
                    // Supprimer l'alerte localement
                    alerts = alerts.filter(alert => alert.id !== alertId);
                    renderAlerts(); // Mettre à jour l'affichage
                } catch (error) {
                    console.error('Erreur lors de la clôture de l\'alerte:', error);
                }
            }
        }

        searchInput.addEventListener('input', renderAlerts);
        filterStatus.addEventListener('change', renderAlerts);

        loadAlerts();
    </script>
</body>
</html>
