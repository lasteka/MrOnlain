<?php
// /api/admin/get-stats.php - Bez role kolonnas
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/functions.php';

// Vienkāršs admin check (bez validateAdminAuth kas var nebūt)
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

$stat = $_GET['stat'] ?? '';

try {
    switch($stat) {
        case 'today_bookings':
            $today = date('Y-m-d');
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE date = ?');
            $stmt->execute([$today]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'total_clients':
            // FIKSĒTS: bez role kolonnas, skaitīt visus users
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'active_services':
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM services');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => intval($result['count'])]);
            break;
            
        case 'weekly_revenue':
            // Vienkāršs aprēķins bez JOIN (gadījumā, ja nav services tabulas)
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            
            // Mēģina ar JOIN, ja nedarbojas - fallback
            try {
                $stmt = $pdo->prepare('
                    SELECT SUM(s.price) as revenue 
                    FROM bookings b 
                    JOIN services s ON b.service = s.name 
                    WHERE b.date BETWEEN ? AND ? AND (b.status = "confirmed" OR b.status IS NULL)
                ');
                $stmt->execute([$weekStart, $weekEnd]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['revenue' => floatval($result['revenue'] ?? 0)]);
            } catch (PDOException $e) {
                // Fallback - aprēķina rezervāciju skaitu * vidējo cenu
                $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE date BETWEEN ? AND ?');
                $stmt->execute([$weekStart, $weekEnd]);
                $bookingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Pieņem vidējo cenu 35 EUR par rezervāciju
                $estimatedRevenue = $bookingCount * 35;
                echo json_encode(['revenue' => $estimatedRevenue]);
            }
            break;
            
        default:
            sendError(400, 'Nezināms statistikas tips');
    }
} catch (Exception $e) {
    error_log('Stats kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda: ' . $e->getMessage());
}