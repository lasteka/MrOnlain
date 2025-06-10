<?php
// /api/admin/manage-bookings.php - UZLABOTS ar rezervāciju atjaunošanas funkciju
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

// Admin autentifikācija
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

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendError(405, 'Metode nav atļauta');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $clientName = trim($_POST['client_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $service = trim($_POST['service'] ?? '');
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $status = $_POST['status'] ?? 'pending';
            $comment = trim($_POST['comment'] ?? '');

            // Validācija
            if (!$id) {
                sendError(400, 'Rezervācijas ID ir obligāts');
            }
            
            if (!$clientName) {
                sendError(400, 'Klienta vārds ir obligāts');
            }
            
            if (!$phone) {
                sendError(400, 'Telefons ir obligāts');
            }
            
            if (!$service) {
                sendError(400, 'Pakalpojums ir obligāts');
            }
            
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                sendError(400, 'Nederīgs datuma formāts');
            }
            
            if (!$time || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $time)) {
                sendError(400, 'Nederīgs laika formāts');
            }

            // Noņem sekundes, ja tādas ir (13:12:00 -> 13:12)
            if (strlen($time) > 5) {
                $time = substr($time, 0, 5);
            }

            if (!in_array($status, ['pending', 'confirmed', 'cancelled'])) {
                sendError(400, 'Nederīgs statuss');
            }

            // Pārbauda vai rezervācija eksistē
            $stmt = $pdo->prepare('SELECT id, user_id FROM bookings WHERE id = ?');
            $stmt->execute([$id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                sendError(404, 'Rezervācija nav atrasta');
            }

            // Pārbauda vai pakalpojums eksistē
            $stmt = $pdo->prepare('SELECT id FROM services WHERE name = ?');
            $stmt->execute([$service]);
            if (!$stmt->fetch()) {
                sendError(400, 'Pakalpojums nav atrasts sistēmā');
            }

            // Atjauno rezervāciju
            $stmt = $pdo->prepare('
                UPDATE bookings SET 
                    name = ?, 
                    phone = ?, 
                    service = ?, 
                    date = ?, 
                    time = ?, 
                    status = ?, 
                    comment = ?
                WHERE id = ?
            ');
            
            $stmt->execute([$clientName, $phone, $service, $date, $time, $status, $comment, $id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Admin atjaunoja rezervāciju: ID=$id, Client=$clientName, Service=$service, Date=$date $time, Status=$status");
                echo json_encode([
                    'success' => true,
                    'message' => 'Rezervācija atjaunota veiksmīgi'
                ]);
            } else {
                // Ja neviens ieraksts netika atjaunots, bet nav kļūdas - iespējams nav izmaiņu
                echo json_encode([
                    'success' => true,
                    'message' => 'Rezervācija saglabāta (nav izmaiņu)'
                ]);
            }
            break;

        case 'delete':
            $id = $_GET['id'] ?? '';
            if (!$id) {
                sendError(400, 'Rezervācijas ID ir obligāts');
            }

            // Pārbauda vai rezervācija eksistē
            $checkStmt = $pdo->prepare('SELECT id, service, date, time, name FROM bookings WHERE id = ?');
            $checkStmt->execute([$id]);
            $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                sendError(404, 'Rezervācija nav atrasta');
            }

            // Dzēš rezervāciju
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Admin dzēsa rezervāciju: ID=$id, Client={$booking['name']}, Service={$booking['service']}, Date={$booking['date']}, Time={$booking['time']}");
                echo json_encode([
                    'success' => true,
                    'message' => 'Rezervācija dzēsta veiksmīgi'
                ]);
            } else {
                sendError(500, 'Neizdevās dzēst rezervāciju');
            }
            break;
            
        default:
            sendError(400, 'Nederīga darbība: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log('Admin rezervāciju DB kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Admin rezervāciju kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda: ' . $e->getMessage());
}
?>