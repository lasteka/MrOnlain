<?php
// /nails-booking/api/admin/manage-services.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '';
    $duration = $_POST['duration'] ?? '';

    if (!$name || !$price || !$duration) {
        sendError(400, 'Visi lauki ir obligāti');
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO services (name, price, duration) VALUES (?, ?, ?)');
        $stmt->execute([$name, $price, $duration]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        error_log('Kļūda pievienojot pakalpojumu: ' . $e->getMessage());
        sendError(500, 'Servera kļūda');
    }
} elseif ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        sendError(400, 'Pakalpojuma ID ir obligāts');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount()) {
            echo json_encode(['success' => true]);
        } else {
            sendError(400, 'Neizdevās dzēst pakalpojumu');
        }
    } catch (PDOException $e) {
        error_log('Kļūda dzēšot pakalpojumu: ' . $e->getMessage());
        sendError(500, 'Servera kļūda');
    }
} else {
    sendError(400, 'Nederīga darbība');
}