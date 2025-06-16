
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "Zx23-Zx81";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$recherche = "SELECT * FROM alertes";
$stmt = $conn->prepare($recherche);
$stmt->execute();
$result = $stmt->get_result();
$points = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $points[] = [
            'id' => $row['id'],
            'motif' => $row['motif'],
            'status' => $row['status'],
            'datetime' => $row['datetime'],
            'commentaire' => $row['commentaire'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tickets</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>         /* Style de la sidebar */
        #sidebar {

        position: none;
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
        <script>
            function takealert(id, status) {
              const commentaire = prompt("Entrez un commentaire :");
              if (commentaire !== null) {
                console.log("Commentaire entré :", commentaire);
                console.log("Fermer alerte ID:", id, "Motif:", commentaire, "Status:", status);

                fetch(`http://192.168.1.3:3000/api/alerts/${id}`, {
                  method: 'PATCH',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ commentaire: commentaire, status: status })
                })
                .then(res => res.ok ? console.log("Alerte fermée") : console.log("Erreur"))
                .catch(err => console.error(err));
                location.reload();
              }else{
                console.log("probleme de commentaire")
              }
            }


             
        </script>
</head>
<body class="bg-gray-100">

  <div class="flex h-screen">
    <div id="sidebar">
        <h2>Actions</h2>
        <ul>
            <li><a href="./index.php">Accueil</a></li>
        </ul>
    </div>
    <!-- Main Content -->
    <main class="flex-1 p-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">TICKETS</h2>
        <button class="bg-purple-100 text-purple-700 px-4 py-2 rounded">Clotuer les Alerte</button>
      </div>

      <button class="mb-4 px-4 py-2 bg-gray-200 rounded">Filtrer</button>

      <div class="overflow-auto">
        <table class="table-fixed min-w-full text-sm text-left">
          <thead>
            <tr class="bg-gray-100 text-gray-600">
              <th class="py-2 px-4">Id</th>
              <th class="py-2 px-4">Sujet</th>
              <th class="py-2 px-4">Site</th> <!--  si motiver faire une forenkey pour recupere non du site -->
              <th class="py-2 px-4">Modifié le</th>
              <th class="py-2 px-4 w-1">Commentaire</th>
              <th class="py-2 px-4">Statut</th>
              <th class="py-2 px-4">Clotuer / Commenter</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($points as $point): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="py-2 px-4 text-blue-600"><?= $point['id'] ?></td>
                <td class="py-2 px-4 text-blue-600"><?= $point['motif'] ?></td>
                <td class="py-2 px-4">Mon client</td>
                <td class="py-2 px-4"><?= $point['datetime'] ?></td>
                <td class="py-2 px-4 overflow-hidden text-ellipsis whitespace-nowrap "><?= $point['commentaire'] ?></td>
                <?php if($point['status'] == "open"){?>
                <td class="py-2 px-4 text-green-600 text-lg"><?= "✅" ?></td>
                <?php }elseif ($point['status'] == "waiting") {?>
                  <td class="py-2 px-4 text-green-600 text-lg"><?= "⏳" ?></td>
                <?php }else{ ?>
                <td class="py-2 px-4 text-red-600 text-lg"><?= "❌" ?></td>
                <?php } ?>
                <td class="py-2 px-4 text-red-600 text-lg"><a href="javascript:void(0)" onclick="takealert(<?= $point['id'] ?>, 'closed')">❌</a><span style="display:inline-block; width: 3.5em;"></span><a href="javascript:void(0)" onclick="takealert(<?= $point['id'] ?>, 'waiting')">💬</a></td>  
            </tr>
            <?php endforeach; ?>
            <!-- Dupliquer cette ligne pour chaque ticket -->
          </tbody>
        </table>
      </div>
    </main>
  </div>

</body>
</html>
