<?php
// /nails-booking/admin/login.php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        if (!$email || !$password) {
            $error = 'E-pasts un parole ir obligāti';
        } else {
            validateEmail($email);

            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $token = generateToken();

                $pdo->prepare('UPDATE admins SET token = ? WHERE id = ?')->execute([$token, $admin['id']]);
                
                // ✅ Salabots URL ceļš
                header('Location: /admin/dashboard.html');
                exit;
            } else {
                $error = 'Nepareizs e-pasts vai parole';
            }
        }
    } catch (PDOException $e) {
        $error = 'Servera kļūda: ' . $e->getMessage();
    } catch (Exception $e) {
        $error = 'Kļūda: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Admin Pieteikšanās</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Admin Pieteikšanās</h2>
        <?php if (!empty($error)): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>E-pasts:
                <input type="email" name="email" required value="<?= htmlspecialchars($email ?? '') ?>">
            </label><br>

            <label>Parole:
                <input type="password" name="password" required>
            </label><br>

            <button type="submit">Pieteikties</button>
        </form>
    </div>
</body>
</html>