<?php
// /nails-booking/api/bookings/submit-anonymous-booking.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$service = trim($data['service'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$comment = trim($data['comment'] ?? '');

try {
    if (!$name || !$phone || !$service || !$date || !$time) {
        sendError(400, 'Visi lauki ir obligāti');
    }
    validatePhone($phone);

    $stmt = $pdo->prepare('INSERT INTO bookings (name, phone, service, date, time, comment) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $service, $date, $time, $comment]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Kļūda rezervācijā: ' . $e->getMessage());
    sendError(500, 'Kļūda veicot rezervāciju');
}