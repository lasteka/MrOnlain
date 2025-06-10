<?php
// /api/auth/check-role.php - Lietotāja lomas pārbaudes API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Uzlabota Authorization header iegūšana
function getAuthorizationHeader() {
    $headers = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    return $headers;
}

try {
    // Iegūst Authorization header
    $token = getAuthorizationHeader();
    
    if (!$token || !str_starts_with($token, 'Bearer ')) {
        sendError(401, 'Nav autorizēts - token nav atrasts');
    }
    
    $rawToken = substr($token, 7);
    
    // Mēģina atrast admin
    $admin = getAdminByToken($pdo, $rawToken);
    if ($admin) {
        echo json_encode([
            'success' => true,
            'role' => 'admin',
            'user' => [
                'id' => $admin['id'],
                'name' => $admin['name'],
                'email' => $admin['email']
            ]
        ]);
        exit;
    }
    
    // Mēģina atrast parasto lietotāju
    $user = getUserByToken($pdo, $rawToken);
    if ($user) {
        echo json_encode([
            'success' => true,
            'role' => 'user',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone']
            ]
        ]);
        exit;
    }
    
    // Nav atrasts neviens lietotājs
    sendError(401, 'Nederīgs tokens');
    
} catch (PDOException $e) {
    error_log('Auth check kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja auth check kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>