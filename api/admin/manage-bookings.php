<?php
// /nails-booking/api/admin/manage-bookings.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/functions.php';

if (!isAdminLoggedIn()) {
    sendError(401, 'Nav autorizēts');
}

$action = $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        sendError(400, 'Rezervācijas ID ir obligāts');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount()) {
            echo json_encode(['success' => true]);
        } else {
            sendError(400, 'Neizdevās dzēst rezervāciju');
        }
    } catch (PDOException $e) {
        error_log('Kļūda dzēšot rezervāciju: ' . $e->getMessage());
        sendError(500, 'Servera kļūda');
    }
} else {
    sendError(400, 'Nederīga darbība');
}