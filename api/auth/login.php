<?php
// /nails-booking/api/auth/login.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError(405, 'Atļauts tikai POST pieprasījums');
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

try {
    if (!$email || !$password) {
        sendError(400, 'E-pasts un parole ir obligāti');
    }
    validateEmail($email);

    // Pārbauda klientu
    $stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        $token = generateToken();
        $pdo->prepare('UPDATE users SET token = ? WHERE id = ?')->execute([$token, $user['id']]);
        $_SESSION['user_id'] = $user['id'];
        echo json_encode([
            'success' => true,
            'token' => $token,
            'role' => 'client'
        ]);
        exit;
    }

    // Pārbauda adminu
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $token = generateToken();
        $pdo->prepare('UPDATE admins SET token = ? WHERE id = ?')->execute([$token, $admin['id']]);
        $_SESSION['admin_id'] = $admin['id'];
        echo json_encode([
            'success' => true,
            'token' => $token,
            'role' => 'admin',
            'redirect' => '/nails-booking/admin/dashboard.html'
        ]);
        exit;
    }

    sendError(401, 'Nepareizs e-pasts vai parole');
} catch (PDOException $e) {
    error_log('Pieteikšanās kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}