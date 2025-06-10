<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/functions.php';

// Tāds pats check kā get-stats.php
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
    sendError(401, 'Nav autorizēts');
}

$token = substr($authHeader, 7);
$admin = getAdminByToken($pdo, $token);

if (!$admin) {
    sendError(401, 'Nederīgs admin token');
}

try {
    $stmt = $pdo->query('SELECT id, name, price, duration FROM services ORDER BY name');
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Admin ielādēja " . count($services) . " pakalpojumus");
    echo json_encode($services);
    
} catch (Exception $e) {
    error_log('get-services kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>