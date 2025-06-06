<?php
// /nails-booking/api/auth/check-role.php - izlabota versija
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// UZLABOTS: Mēģina dažādus veidus, kā iegūt Authorization galveni
function getAuthorizationHeader() {
    $headers = null;
    
    // 1. mēģinājums - HTTP_AUTHORIZATION
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    // 2. mēģinājums - REDIRECT_HTTP_AUTHORIZATION (priekš Apache)
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    // 3. mēģinājums - getallheaders() funkcija
    elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        // Meklē 'Authorization' vai 'authorization'
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    return $headers;
}

$token = getAuthorizationHeader();

if (!$token || !str_starts_with($token, 'Bearer ')) {
    error_log("DEBUG check-role - Token nav atrasts vai nav Bearer formātā: " . ($token ?: 'nav'));
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7); // Noņem "Bearer " no sākuma
error_log("DEBUG check-role - Pārbauda token: " . substr($rawToken, 0, 10) . "...");

try {
    // Pārbauda klientu
    $user = getUserByToken($pdo, $rawToken);
    if ($user) {
        error_log("DEBUG check-role - Klients atrasts: " . $user['name']);
        echo json_encode([
            'role' => 'client',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone']
            ]
        ]);
        exit;
    }

    // Pārbauda adminu
    $admin = getAdminByToken($pdo, $rawToken);
    if ($admin) {
        error_log("DEBUG check-role - Admin atrasts");
        echo json_encode([
            'role' => 'admin',
            'user' => [
                'id' => $admin['id'],
                'name' => $admin['name'] ?? 'Admin'
            ]
        ]);
        exit;
    }

    error_log("DEBUG check-role - Nedz klients, nedz admin nav atrasts");
    sendError(401, 'Nederīgs tokens');
    
} catch (PDOException $e) {
    error_log('DEBUG check-role - PDO kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
} catch (Exception $e) {
    error_log('DEBUG check-role - Vispārēja kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>