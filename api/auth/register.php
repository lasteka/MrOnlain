<?php
// /nails-booking/api/auth/register.php
header('Content-Type: application/json; charset=utf-8');

$allowedOrigins = ['http://127.0.0.1'];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data)) {
        sendError(400, 'Nederīgs JSON formāts');
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$name || !$phone || !$email || !$password) {
        sendError(400, 'Visi lauki ir obligāti');
    }

    // Validācija ar izņēmumu noķeršanu
    try {
        validateEmail($email);
        validatePhone($phone);
        validatePassword($password);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }

    // Pārbauda, vai e-pasts jau eksistē
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError(400, 'Šis e-pasts jau ir reģistrēts');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $token = generateToken();

    $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, token) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $email, $passwordHash, $token]);

    session_start();
    $_SESSION['user_id'] = $pdo->lastInsertId();

    // PIEVIENOTS: role vērtība JSON atbildē
    echo json_encode([
        'success' => true,
        'token' => $token,
        'role' => 'client'  // Šī ir galvenā izmaiņa!
    ]);

} catch (PDOException $e) {
    error_log('Reģistrācijas kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>