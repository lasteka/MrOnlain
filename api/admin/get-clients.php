<?php
// /api/admin/get-clients.php - Klientu iegūšana admin panelim
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

try {
    // Iegūst klientus ar papildu informāciju par rezervācijām
    $stmt = $pdo->prepare('
        SELECT 
            u.*,
            COUNT(b.id) as bookings_count,
            MAX(b.date) as last_visit
        FROM users u 
        LEFT JOIN bookings b ON u.id = b.user_id 
        GROUP BY u.id 
        ORDER BY u.name ASC
    ');
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatē rezultātus
    $formattedClients = [];
    foreach ($clients as $client) {
        $formattedClients[] = [
            'id' => intval($client['id']),
            'name' => $client['name'],
            'email' => $client['email'],
            'phone' => $client['phone'],
            'bookings_count' => intval($client['bookings_count']),
            'last_visit' => $client['last_visit'] ? date('d.m.Y', strtotime($client['last_visit'])) : null,
            'created_at' => $client['created_at']
        ];
    }
    
    error_log("Admin ielādēja " . count($formattedClients) . " klientus");
    echo json_encode($formattedClients);
    
} catch (PDOException $e) {
    error_log('Admin klientu ielādes kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja admin klientu kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>