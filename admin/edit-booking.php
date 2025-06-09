<?php
// /admin/edit-booking.php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $imagePath = $booking['image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = 'uploads/' . uniqid() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../public/' . $imagePath);
    }

    $stmt = $pdo->prepare('UPDATE bookings SET date = ?, time = ?, comment = ?, image = ? WHERE id = ?');
    $stmt->execute([$date, $time, $comment, $imagePath, $id]);
    header('Location: /admin/bookings.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Rediģēt rezervāciju</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Rediģēt rezervāciju</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Datums: <input type="date" name="date" value="<?= htmlspecialchars($booking['date']) ?>" required></label>
            <label>Laiks: <input type="time" name="time" value="<?= htmlspecialchars($booking['time']) ?>" required></label>
            <label>Komentārs: <textarea name="comment"><?= htmlspecialchars($booking['comment']) ?></textarea></label>
            <label>Attēls: <input type="file" name="image" accept="image/*"></label>
            <?php if ($booking['image']): ?>
                <p>Pašreizējais attēls: <img src="/public/<?= htmlspecialchars($booking['image']) ?>" width="100"></p>
            <?php endif; ?>
            <button type="submit">Saglabāt</button>
        </form>
        <a href="/admin/bookings.php">Atpakaļ</a>
    </div>
</body>
</html>