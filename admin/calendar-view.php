<?php
// /admin/calendar-view.php
session_start();
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$stmt = $pdo->query('SELECT date, start_time, end_time, is_available FROM working_hours ORDER BY date DESC');
$hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Darba laiku pārvaldība</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Darba laiki</h2>
        <table>
            <tr>
                <th>Datums</th>
                <th>Sākuma laiks</th>
                <th>Beigu laiks</th>
                <th>Pieejams</th>
                <th>Darbības</th>
            </tr>
            <?php foreach ($hours as $hour): ?>
                <tr>
                    <td><?= htmlspecialchars($hour['date']) ?></td>
                    <td><?= htmlspecialchars($hour['start_time']) ?></td>
                    <td><?= htmlspecialchars($hour['end_time']) ?></td>
                    <td><?= $hour['is_available'] ? 'Jā' : 'Nē' ?></td>
                    <td>
                        <a href="/admin/actions.php?action=edit-hours&date=<?= $hour['date'] ?>">Rediģēt</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="/admin/actions.php?action=add-hours">Pievienot darba laiku</a>
        <a href="/admin/dashboard.html">Atpakaļ</a>
    </div>
</body>
</html>