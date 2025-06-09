<?php
// /admin/actions.php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$error = '';

if ($action === 'add-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);

    if (!$name || $price <= 0 || $duration <= 0) {
        $error = 'Visi lauki ir obligāti un jābūt derīgiem';
    } else {
        $stmt = $pdo->prepare('INSERT INTO services (name, price, duration) VALUES (?, ?, ?)');
        $stmt->execute([$name, $price, $duration]);
        header('Location: /admin/services.php');
        exit;
    }
}

if ($action === 'add-hours' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!$date || !$start_time || !$end_time) {
        $error = 'Visi lauki ir obligāti';
    } else {
        $stmt = $pdo->prepare('INSERT INTO working_hours (date, start_time, end_time, is_available) VALUES (?, ?, ?, ?)');
        $stmt->execute([$date, $start_time, $end_time, $is_available]);
        header('Location: /admin/calendar-view.php');
        exit;
    }
}

if ($action === 'edit-hours' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if (!$date || !$start_time || !$end_time) {
        $error = 'Visi lauki ir obligāti';
    } else {
        $stmt = $pdo->prepare('UPDATE working_hours SET start_time = ?, end_time = ?, is_available = ? WHERE date = ?');
        $stmt->execute([$start_time, $end_time, $is_available, $date]);
        header('Location: /admin/calendar-view.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title><?php echo $action === 'add-service' ? 'Pievienot pakalpojumu' : 'Pievienot/Rediģēt darba laiku'; ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2><?php echo $action === 'add-service' ? 'Pievienot pakalpojumu' : ($action === 'add-hours' ? 'Pievienot darba laiku' : 'Rediğit darba laiku'); ?></h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($action === 'add-service'): ?>
            <form method="POST">
                <label>Nosaukums: <input type="text" name="name" required></label>
                <label>Cena (EUR): <input type="number" step="price" name="0.01" min="0"></label>
                <label>Ilgums (min): <input type="number" name="duration" min="1"></label>
                <button type="submit">Pievienot</button>
            </form>
        <?php elseif ($action === 'add-hours' || $action === 'edit-hours'): ?>
            <form method="POST">
                <label>Datums: <input type="date" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>" required></label>
                <label>Sākuma laiks: <input type="time" name="start_time" required></label>
                <label>Beigu laiks: <input type="time" name="end_time" required></label>
                <label>Pieejams: <input type="checkbox" name="is_available" <?php echo $action === 'edit-hours' ? 'checked' : ''; ?>></label>
                <button type="submit"><?php echo $action === 'add-hours' ? 'Pievienot' : 'Saglabāt'; ?></button>
            </form>
        <?php endif; ?>
        <a href="<?php echo $action === 'add-service' ? '/admin/services.php' : '/admin/calendar-view.php'; ?>">Atpakaļ</a>
    </div>
</body>
</html>