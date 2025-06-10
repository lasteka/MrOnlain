<?php
// /api/admin/manage-bookings.php - FIKSĒTA AUTENTIFIKĀCIJA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/functions.php';

// FIKSĒTS: Standarta autentifikācija (tāda pati kā citiem API)
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

$action = $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        sendError(400, 'Rezervācijas ID ir obligāts');
    }

    try {
        // Pārbauda vai rezervācija eksistē
        $checkStmt = $pdo->prepare('SELECT id, service, date, time FROM bookings WHERE id = ?');
        $checkStmt->execute([$id]);
        $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            sendError(404, 'Rezervācija nav atrasta');
        }

        // Dzēš rezervāciju
        $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("Admin dzēsa rezervāciju: ID=$id, Service={$booking['service']}, Date={$booking['date']}, Time={$booking['time']}");
            echo json_encode([
                'success' => true,
                'message' => 'Rezervācija dzēsta veiksmīgi'
            ]);
        } else {
            sendError(500, 'Neizdevās dzēst rezervāciju');
        }
    } catch (PDOException $e) {
        error_log('Kļūda dzēšot rezervāciju: ' . $e->getMessage());
        sendError(500, 'Servera kļūda');
    }
} else {
    sendError(400, 'Nederīga darbība');
}
?>