<?php
// /nails-booking/api/bookings/submit-booking.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$service = trim($data['service'] ?? '');
$comment = trim($data['comment'] ?? '');

try {
    if (!$date || !$time || !$service) {
        sendError(400, 'Datums, laiks un pakalpojums ir obligāti');
    }

    $userId = $user ? $user['id'] : null;
    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, name, phone, service, date, time, comment) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $data['name'] ?? '', $data['phone'] ?? '', $service, $date, $time, $comment]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Kļūda rezervācijā: ' . $e->getMessage());
    sendError(500, 'Kļūda veicot rezervāciju');
}