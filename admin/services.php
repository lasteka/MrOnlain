<?php
// /admin/services.php

session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$stmt = $pdo->query('SELECT id, name, price, duration FROM services ORDER BY name');
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pakalpojumu pārvaldība</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Pakalpojumi</h2>
        <table>
            <tr>
                <th>Nosaukums</th>
                <th>Cena (EUR)</th>
                <th>Ilgums (min)</th>
                <th>Darbības</th>
            </tr>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?= htmlspecialchars($service['name']) ?></td>
                    <td><?= htmlspecialchars($service['price']) ?></td>
                    <td><?= htmlspecialchars($service['duration']) ?></td>
                    <td>
                        <a href="/admin/edit-service.php?id=<?= $service['id'] ?>">Rediģēt</a>
                        <a href="/api/admin/manage-services.php?action=delete&id=<?= $service['id'] ?>" onclick="return confirm('Vai tiešām dzēst?')">Dzēst</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="/admin/actions.php?action=add-service">Pievienot pakalpojumu</a>
        <a href="/admin/dashboard.html">Atpakaļ</a>
    </div>
</body>
</html>