<?php
// /nails-booking/api/auth/check-role.php
header('Content-Type: application/json');
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
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7); // Noņem "Bearer " no sākuma

try {
    // Pārbauda klientu
    $user = getUserByToken($pdo, $rawToken);
    if ($user) {
        echo json_encode(['role' => 'client']);
        exit;
    }

    // Pārbauda adminu
    $admin = getAdminByToken($pdo, $rawToken);
    if ($admin) {
        echo json_encode(['role' => 'admin']);
        exit;
    }

    sendError(401, 'Nederīgs tokens');
    
} catch (PDOException $e) {
    error_log('Check-role kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
} catch (Exception $e) {
    error_log('Vispārēja check-role kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>