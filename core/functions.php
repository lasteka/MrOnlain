<?php
// /nails-booking/core/functions.php
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
    try {
        error_log("getUserByToken izsaukts ar token: " . $token); // Debug
        
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("getUserByToken rezultāts: " . ($user ? json_encode($user) : 'nav atrasts')); // Debug
        
        return $user;
    } catch (PDOException $e) {
        error_log("getUserByToken kļūda: " . $e->getMessage());
        return false;
    }
}

function getAdminByToken($pdo, $token) {
    try {
        error_log("getAdminByToken izsaukts ar token: " . $token); // Debug
        
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE token = ?');
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("getAdminByToken rezultāts: " . ($admin ? json_encode($admin) : 'nav atrasts')); // Debug
        
        return $admin;
    } catch (PDOException $e) {
        error_log("getAdminByToken kļūda: " . $e->getMessage());
        return false;
    }
}
