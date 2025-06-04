<?php
// /nails-booking/api/bookings/delete-booking.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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
$admin = getAdminByToken($pdo, $rawToken);

if (!$user && !$admin) {
    sendError(403, 'Nederīgs tokens');
}

$id = $_GET['id'] ?? '';
if (!$id) {
    sendError(400, 'Rezervācijas ID ir obligāts');
}

try {
    $query = 'DELETE FROM bookings';
    $params = [];
    if ($user) {
        $query .= ' WHERE id = ? AND user_id = ?';
        $params = [$id, $user['id']];
    } else {
        $query .= ' WHERE id = ?';
        $params = [$id];
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount()) {
        echo json_encode(['success' => true]);
    } else {
        sendError(400, 'Neizdevās dzēst rezervāciju');
    }
} catch (PDOException $e) {
    error_log('Kļūda dzēšot rezervāciju: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}