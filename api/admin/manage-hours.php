<?php
// /api/admin/manage-hours.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
        sendError(400, 'Darba laika ID ir obligāts');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM working_hours WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount()) {
            echo json_encode(['success' => true]);
        } else {
            sendError(400, 'Neizdevās dzēst darba laiku');
        }
    } catch (PDOException $e) {
        error_log('Kļūda dzēšot darba laiku: ' . $e->getMessage());
        sendError(500, 'Servera kļūda');
    }
} else {
    sendError(400, 'Nederīga darbība');
}