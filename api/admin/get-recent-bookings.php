<?php
// /api/admin/get-recent-bookings.php - FIKSĒTA AUTENTIFIKĀCIJA
header('Content-Type: application/json; charset=utf-8');
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

try {
    // Iegūst jaunākās rezervācijas (pēdējās 10)
    $stmt = $pdo->prepare('
        SELECT 
            b.*,
            u.name as client_name,
            u.email as client_email,
            u.phone as client_phone
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 10
    ');
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatē rezultātus
    $formattedBookings = [];
    foreach ($bookings as $booking) {
        $formattedBookings[] = [
            'id' => $booking['id'],
            'client_name' => $booking['client_name'] ?: ($booking['name'] ?: 'Nav norādīts'),
            'client_phone' => $booking['client_phone'] ?: ($booking['phone'] ?: 'Nav norādīts'),
            'client_email' => $booking['client_email'] ?: ($booking['email'] ?: 'Nav norādīts'),
            'service' => $booking['service'] ?: 'Nav norādīts',
            'date' => $booking['date'],
            'time' => $booking['time'],
            'status' => $booking['status'] ?: 'pending',
            'comment' => $booking['comment'],
            'image' => $booking['image'],
            'created_at' => $booking['created_at']
        ];
    }
    
    error_log("Admin ielādēja " . count($formattedBookings) . " rezervācijas");
    echo json_encode($formattedBookings);
    
} catch (PDOException $e) {
    error_log('Admin rezervāciju ielādes kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja admin kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>