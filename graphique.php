<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "Zx23-Zx81";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}

// Récupérer l'id_point depuis l'URL
$id_point = isset($_GET['id_point']) ? (int)$_GET['id_point'] : 0;

// Vérifier que l'id_point est valide
if ($id_point <= 0) {
    die("ID Point invalide.");
}

$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : null;
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : null;

$sql = "SELECT heure, tens, temp FROM mesures WHERE id_point = ?";
$params = [$id_point];
$types = "i"; // 'i' car id_point est un entier

// Si date_debut est spécifiée, on ajoute la condition sur cette date
if ($date_debut && !$date_fin) {
    $sql .= " AND heure >= ?";
    $params[] = $date_debut;
    $types .= "s"; // 's' pour string
}

// Si date_fin est spécifiée, on ajoute la condition sur cette date
if (!$date_debut && $date_fin) {
    $sql .= " AND heure <= ?";
    $params[] = $date_fin;
    $types .= "s"; // 's' pour string
}

// Si les deux dates sont spécifiées, on ajoute une plage entre date_debut et date_fin
if ($date_debut && $date_fin) {
    $sql .= " AND heure BETWEEN ? AND ?";
    $params[] = $date_debut;
    $params[] = $date_fin;
    $types .= "ss"; // 's' pour string (pour les deux dates)
}

$sql .= " ORDER BY heure ASC";

// Préparation de la requête
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erreur de préparation : " . $conn->error);
}

// Création des références pour les paramètres
$bind_names = [];
$bind_names[] = $types;

foreach ($params as $key => $value) {
    $bind_names[] = &$params[$key];
}

// Appel dynamique de bind_param
call_user_func_array([$stmt, 'bind_param'], $bind_names);

// Exécuter la requête
$stmt->execute();
$result = $stmt->get_result();

// Récupérer les résultats dans un tableau
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();
$conn->close();


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graphique avec Filtrage par Date</title>
    <script src="https://cdn.jsdelivr.net/npm/echarts/dist/echarts.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        h1 {
            text-align: center;
            margin-top: 20px;
        }
        #chart {
            width: 100%;
            height: calc(100vh - 200px); /* Hauteur de l'écran moins la section du formulaire */
        }
        .form-container {
            text-align: center;
            margin: 20px;
        }
        .form-container input {
            padding: 10px;
            margin: 5px;
            font-size: 16px;
        }
        .form-container button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Graphique de Tension et Température</h1>

    <!-- Formulaire de filtre -->
    <div class="form-container">
        <form method="GET" action="">
            <input type="hidden" name="id_point" value="<?php echo htmlspecialchars($id_point); ?>">
            <label for="date_debut">Date Début:</label>
            <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>">
            <label for="date_fin">Date Fin:</label>
            <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>">
            <button type="submit">Filtrer</button>
        </form>
    </div>

    <!-- Conteneur du graphique -->
    <div id="chart"></div>

    <script>
        // Conversion des données PHP en JavaScript
        const data = <?php echo json_encode($data); ?>;

        // Préparer les données pour ECharts
        const labels = data.map(item => item.heure); // Heures
        const tensData = data.map(item => item.tens); // Tension
        const tempData = data.map(item => item.temp); // Température

        // Initialiser le graphique
        const chartDom = document.getElementById('chart');
        const myChart = echarts.init(chartDom);

        // Options de configuration du graphique
        const option = {
            title: {
                text: 'Tension et Température',
                left: 'center',
                textStyle: {
                    fontSize: 20,
                    fontWeight: 'bold'
                }
            },
            tooltip: {
                trigger: 'axis',
                formatter: function (params) {
                    let content = `<strong>${params[0].axisValue}</strong><br>`;
                    params.forEach(param => {
                        content += `${param.marker} ${param.seriesName}: ${param.data}<br>`;
                    });
                    return content;
                }
            },
            legend: {
                data: ['Tension (V)', 'Température (°C)'],
                top: '10%'
            },
            xAxis: {
                type: 'category',
                data: labels,
                axisLabel: {
                    rotate: 45, // Rotation des labels pour les rendre lisibles
                    interval: 0
                }
            },
            yAxis: {
                type: 'value',
                name: 'Valeurs',
                axisLabel: {
                    formatter: '{value}'
                }
            },
            series: [
                {
                    name: 'Tension (V)',
                    type: 'line',
                    data: tensData,
                    smooth: true,
                    lineStyle: {
                        color: 'rgba(255, 99, 132, 1)'
                    },
                    areaStyle: {
                        color: 'rgba(255, 99, 132, 0.2)'
                    },
                    itemStyle: {
                    color: 'rgba(255, 99, 132, 1)' // Couleur des points pour la tension
                    }
                },
                {
                    name: 'Température (°C)',
                    type: 'line',
                    data: tempData,
                    smooth: true,
                    lineStyle: {
                        color: 'rgba(54, 162, 235, 1)'
                    },
                    areaStyle: {
                        color: 'rgba(54, 162, 235, 0.2)'
                    },
                    itemStyle: {
                    color: 'rgba(54, 162, 235, 1)' // Couleur des points pour la tension
                    }
                }
            ]
        };

        // Appliquer les options
        myChart.setOption(option);
    </script>
</body>
</html>
