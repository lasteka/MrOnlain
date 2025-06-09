<?php
// /admin/edit-service.php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    header('Location: /admin/services.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);

    if (!$name || $price <= 0 || $duration <= 0) {
        $error = 'Visi lauki ir obligāti un jābūt derīgiem';
    } else {
        $stmt = $pdo->prepare('UPDATE services SET name = ?, price = ?, duration = ? WHERE id = ?');
        $stmt->execute([$name, $price, $duration, $id]);
        header('Location: /admin/services.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Rediģēt pakalpojumu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Rediģēt pakalpojumu</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Nosaukums: <input type="text" name="name" value="<?= htmlspecialchars($service['name']) ?>" required></label>
            <label>Cena (EUR): <input type="number" name="price" step="0.01" min="0" value="<?= number_format($service['price'], 2) ?>" required></label>
            <label>Ilgums (min): <input type="number" name="duration" min="1" value="<?= htmlspecialchars($service['duration']) ?>" required></label>
            <button type="submit">Saglabāt</button>
        </form>
        <a href="/admin/services.php">Atpaga</a>
    </div>
</body>
</html>