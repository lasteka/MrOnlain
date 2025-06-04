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

try {
    $query = 'SELECT b.*, u.name AS user_name FROM bookings b LEFT JOIN users u ON b.user_id = u.id';
    $params = [];
    if ($user) {
        $query .= ' WHERE b.user_id = ?';
        $params = [$user['id']];
    }
    $query .= ' ORDER BY b.date DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bookings);
} catch (PDOException $e) {
    error_log('Kļūda ielādējot rezervācijas: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}