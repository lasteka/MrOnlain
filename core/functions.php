<?php
// /nails-booking/core/functions.php - izlabota versija
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
        error_log("DEBUG getUserByToken - izsaukts ar token: " . substr($token, 0, 10) . "...");
        
        // SVARĪGI: atlasa VISUS nepieciešamos laukus!
        $stmt = $pdo->prepare('SELECT id, name, email, phone FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            error_log("DEBUG getUserByToken - lietotājs atrasts: ID={$user['id']}, Name={$user['name']}, Email={$user['email']}, Phone={$user['phone']}");
        } else {
            error_log("DEBUG getUserByToken - lietotājs NAV atrasts");
            
            // Papildu debug - pārbauda vai token vispār eksistē
            $tokenCheck = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE token = ?');
            $tokenCheck->execute([$token]);
            $tokenExists = $tokenCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenExists['count'] > 0) {
                error_log("DEBUG getUserByToken - Token eksistē datubāzē, bet user nav atrasts (iespējams citas problēmas)");
            } else {
                error_log("DEBUG getUserByToken - Token NEEKSISTĒ datubāzē!");
            }
        }
        
        return $user;
    } catch (PDOException $e) {
        error_log("DEBUG getUserByToken - Datubāzes kļūda: " . $e->getMessage());
        return false;
    }
}

function getAdminByToken($pdo, $token) {
    try {
        error_log("DEBUG getAdminByToken - izsaukts ar token: " . substr($token, 0, 10) . "...");
        
        // Pārbauda vai admin tabula eksistē
        $stmt = $pdo->prepare('SELECT id, name FROM admins WHERE token = ?');
        $stmt->execute([$token]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            error_log("DEBUG getAdminByToken - admin atrasts: " . json_encode($admin));
        } else {
            error_log("DEBUG getAdminByToken - admin nav atrasts");
        }
        
        return $admin;
    } catch (PDOException $e) {
        error_log("DEBUG getAdminByToken - kļūda (iespējams admins tabula neeksistē): " . $e->getMessage());
        return false;
    }
}

// Papildu debug funkcija
function debugTokenInDatabase($pdo, $token) {
    try {
        error_log("DEBUG - Meklē token datubāzē: " . substr($token, 0, 10) . "...");
        
        // Pārbauda users tabulu
        $stmt = $pdo->prepare('SELECT id, name, email, phone, token FROM users WHERE token = ?');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            error_log("DEBUG - Users tabula: token atrasts, user=" . json_encode($user));
        } else {
            error_log("DEBUG - Users tabula: token NAV atrasts");
            
            // Parāda pēdējos 5 token no users tabulas
            $recentTokens = $pdo->query('SELECT id, name, email, LEFT(token, 10) as token_start FROM users WHERE token IS NOT NULL ORDER BY id DESC LIMIT 5');
            $tokens = $recentTokens->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG - Pēdējie 5 token users tabulā: " . json_encode($tokens));
        }
        
    } catch (PDOException $e) {
        error_log("DEBUG - Kļūda debugojot token: " . $e->getMessage());
    }
}
?>