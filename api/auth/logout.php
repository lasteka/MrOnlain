<?php
// /api/auth/logout.php - IZLABOTS
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$token = getAuthorizationHeader();

// DEBUG
error_log("DEBUG logout - Raw token header: " . ($token ?: 'NAV'));

if (!$token || !str_starts_with($token, 'Bearer ')) {
    error_log("DEBUG logout - Token validation failed: " . ($token ?: 'NAV'));
    sendError(401, 'Nav autorizēts - token nav atrasts');
}

$rawToken = substr($token, 7);
error_log("DEBUG logout - Processing token: " . substr($rawToken, 0, 10) . "...");

try {
    // Notīra token no datubāzes
    $stmt = $pdo->prepare('UPDATE users SET token = NULL WHERE token = ?');
    $userResult = $stmt->execute([$rawToken]);
    $userRows = $stmt->rowCount();
    
    $stmt = $pdo->prepare('UPDATE admins SET token = NULL WHERE token = ?');
    $adminResult = $stmt->execute([$rawToken]);
    $adminRows = $stmt->rowCount();
    
    error_log("DEBUG logout - Cleared tokens: users=$userRows, admins=$adminRows");
    
    // Notīra session
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Veiksmīgi izrakstījies',
        'cleared_tokens' => [
            'users' => $userRows,
            'admins' => $adminRows
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Izrakstīšanās kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Vispārēja logout kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>