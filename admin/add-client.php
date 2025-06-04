<?php
// /nails-booking/admin/add-client.php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: /nails-booking/admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if (!$name || !$phone || !$email || !$password) {
            $error = 'Visi lauki ir obligāti';
        } else {
            validateEmail($email);
            validatePhone($phone);
            validatePassword($password);

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Šis e-pasts jau ir reģistrēts';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $token = generateToken();
                $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, token) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$name, $phone, $email, $passwordHash, $token]);
                header('Location: /nails-booking/admin/dashboard.html');
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = 'Datubāzes kļūda: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pievienot klientu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Pievienot klientu</h2>
        <?php if (isset($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>Vārds: <input type="text" name="name" required></label>
            <label>Telefons: <input type="tel" name="phone" required></label>
            <label>E-pasts: <input type="email" name="email" required></label>
            <label>Parole: <input type="password" name="password" required></label>
            <button type="submit">Pievienot</button>
        </form>
        <a href="/nails-booking/admin/dashboard.html">Atpakaļ</a>
    </div>
</body>
</html>