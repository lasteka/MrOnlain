<?php
// /api/admin/get-stats.php - Jaunais fails statistikai
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/functions.php';

// Pārbauda admin autentifikāciju
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$token || !str_starts_with($token, 'Bearer ')) {
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7);
$admin = getAdminByToken($pdo, $rawToken);
if (!$admin) {
    sendError(401, 'Nederīgs admin tokens');
}

$stat = $_GET['stat'] ?? '';

try {
    switch($stat) {
        case 'today_bookings':
            // Šodienas rezervāciju skaits
            $today = date('Y-m-d');
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE date = ?');
            $stmt->execute([$today]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'total_clients':
            // Kopējais klientu skaits
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'active_services':
            // Aktīvo pakalpojumu skaits
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM services');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'weekly_revenue':
            // Nedēļas ieņēmumi (vienkāršots)
            echo json_encode(['revenue' => 1240]);
            break;
            
        default:
            sendError(400, 'Nezināms statistikas tips');
    }
} catch (PDOException $e) {
    error_log('Statistikas kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>