<?php
// /api/auth/logout.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');header('Access-Control-Allow-Methods: POST, OPTIONS');
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
try {
    $stmt = $pdo->prepare('UPDATE users SET token = NULL WHERE token = ?');
    $stmt->execute([$rawToken]);
    $stmt = $pdo->prepare('UPDATE admins SET token = NULL WHERE token = ?');
    $stmt->execute([$rawToken]);
    session_destroy();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Izrakstīšanās kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}