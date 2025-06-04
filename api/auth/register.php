<?php
// /nails-booking/api/auth/register.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://127.0.0.1');
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

    // Validācija
    if (!$name || !$phone || !$email || !$password) {
        sendError(400, 'Visi lauki ir obligāti');
    }
    validateEmail($email);
    validatePhone($phone);
    validatePassword($password);

    // Pārbauda, vai e-pasts jau eksistē
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError(400, 'Šis e-pasts jau ir reģistrēts');
    }

    // Šifrē paroli un ģenerē tokenu
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $token = generateToken();

    // Ievieto lietotāju
    $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, token) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $email, $passwordHash, $token]);

    // Sāk sesiju
    session_start();
    $_SESSION['user_id'] = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'token' => 'client',
        'token' => $token
    ]);
} catch (PDOException $e) {
    error_log('Reģistrācijas kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Vispārēja kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda: ' . $e->getMessage());
}