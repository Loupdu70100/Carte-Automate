<?php
// Connexion MySQLi
$servername = "localhost";
$username = "root";
$password = "Zx23-Zx81";
$dbname = "ubmap";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

$points = [];
$result = $conn->query("SELECT * FROM points");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $points[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Points</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6">Liste des Points</h1>

        <table class="min-w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead class="text-black">
                <tr>
                    <th class="text-left px-6 py-3">Nom</th>
                    <th class="text-left px-6 py-3">Adresse IP</th>
                    <th class="text-left px-6 py-3">Port</th>
                    <th class="text-left px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($points as $point): ?>
                <tr class="hover:bg-gray-100">
                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($point['nom']) ?></td>
                    <td class="px-6 py-4"><?= $point['ip_address'] ?></td>
                    <td class="px-6 py-4"><?= $point['port'] ?></td>
                    <td class="px-6 py-4">
                        <a href="edit_point.php?id=<?= $point['id'] ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Modifier</a>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($points)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Aucun point trouvé.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
