<?php
// /admin/login.php - Izlabots admin pieteikšanās fails
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

            // IZLABOTS: Meklē gan pēc email, gan username
            $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ? OR username = ?');
            $stmt->execute([$email, $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $token = generateToken();

                // Saglabā token datubāzē
                $pdo->prepare('UPDATE admins SET token = ? WHERE id = ?')->execute([$token, $admin['id']]);
                
                // IZLABOTS: Saglabā token localStorage izmantošanai
                echo '<script>
                    localStorage.setItem("admin_token", "' . $token . '");
                    window.location.href = "/admin/dashboard.html";
                </script>';
                exit;
            } else {
                $error = 'Nepareizs e-pasts vai parole';
            }
        }
    } catch (PDOException $e) {
        $error = 'Servera kļūda: ' . $e->getMessage();
        error_log('Admin login kļūda: ' . $e->getMessage());
    } catch (Exception $e) {
        $error = 'Kļūda: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pieteikšanās - Nagu Studija</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-header">
            <h1>💅 Admin Panelis</h1>
            <p>Piesakies, lai pārvaldītu nagu studiju</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="email">E-pasts vai lietotājvārds</label>
                <input 
                    type="text" 
                    id="email"
                    name="email" 
                    class="form-control"
                    required 
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    placeholder="admin@gmail.com"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Parole</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    class="form-control"
                    required
                    placeholder="Ievadi savu paroli"
                >
            </div>
            
            <button type="submit" class="btn-login">
                🔑 Pieteikties Admin Panelī
            </button>
        </form>
        
        <!-- Demo informācija -->
        <!-- <div class="demo-info">
            <strong>Demo pieteikšanās:</strong><br>
            📧 E-pasts: <code>admin@gmail.com</code><br>
            🔐 Parole: <code>admin123</code>
        </div> -->
    </div>
    
    <script>
        // Pārbauda vai jau ir pieteicies
        if (localStorage.getItem('admin_token')) {
            window.location.href = '/admin/dashboard.html';
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                alert('Lūdzu, aizpildi visus laukus!');
                return;
            }
        });
    </script>
</body>
</html>