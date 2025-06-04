<?php
// /kursa-darbi/nails-booking/core/functions.php
function sendError($code, $message) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError(400, 'Nederīgs e-pasta formāts');
    }
}

function validatePhone($phone) {
    if (!preg_match('/^\+?\d{8,}$/', $phone)) {
        sendError(400, 'Nederīgs telefona numurs');
    }
}

function validatePassword($password) {
    if (strlen($password) < 8) {
        sendError(400, 'Parolei jābūt vismaz 8 simbolus garai');
    }
}

function generateToken() {
    return bin2hex(random_bytes(16));
}

function getUserByToken($pdo, $token) {
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE token = ?');
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAdminByToken($pdo, $token) {
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE token = ?');
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>