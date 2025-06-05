<?php
// /nails-booking/api/bookings/get-user-bookings.php
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

// Uzlabota Authorization galvenes iegūšana
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
    $token = getAuthorizationHeader();
    
    // Debug izvade
    error_log("Authorization header: " . ($token ? $token : 'nav atrasts'));
    
    if (!$token || !str_starts_with($token, 'Bearer ')) {
        error_log("Token nav pareizs: " . ($token ? $token : 'nav'));
        sendError(401, 'Nav autorizēts');
    }

    $rawToken = substr($token, 7);
    error_log("Raw token: " . $rawToken);

    // Pārbauda lietotāju
    $user = getUserByToken($pdo, $rawToken);
    error_log("User found: " . ($user ? 'jā' : 'nē'));
    
    if (!$user) {
        sendError(401, 'Nederīgs tokens');
    }

    // Iegūst lietotāja rezervācijas
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE user_id = ? ORDER BY date DESC, time DESC');
    $stmt->execute([$user['id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Bookings count: " . count($bookings));
    
    echo json_encode($bookings);

} catch (PDOException $e) {
    error_log('Datubāzes kļūda get-user-bookings: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja kļūda get-user-bookings: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
