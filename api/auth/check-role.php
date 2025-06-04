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

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$token || !str_starts_with($token, 'Bearer ')) {
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7);
$user = getUserByToken($pdo, $rawToken);
if ($user) {
    echo json_encode(['role' => 'client']);
    exit;
}

$admin = getAdminByToken($pdo, $rawToken);
if ($admin) {
    echo json_encode(['role' => 'admin']);
    exit;
}

sendError(401, 'Nederīgs tokens');