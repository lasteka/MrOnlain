<?php
// /kursa-darbi/nails-booking/api/bookings/get-availability.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

$date = $_GET['date'] ?? '';
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    sendError(400, 'Nederīgs datuma formāts');
}

try {
    // Iegūstam darba laikus
    $stmt = $pdo->prepare('SELECT start_time, end_time, is_available FROM working_hours WHERE date = ?');
    $stmt->execute([$date]);
    $hours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hours || !$hours['is_available']) {
        echo json_encode([]); // TUKSS masīvs, ja nav darba laika vai tas nav pieejams
        
        exit;
    }

    // Iegūstam aizņemtos laikus
    $stmt = $pdo->prepare('SELECT time FROM bookings WHERE date = ?');
    $stmt->execute([$date]);
    $booked = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'time');

    // Ģenerējam pieejamos laikus
    $start = strtotime($hours['start_time']);
    $end = strtotime($hours['end_time']);
    $slots = [];
    $interval = 30 * 60; // 30 minūšu sloti

    for ($time = $start; $time < $end; $time += $interval) {
        $timeStr = date('H:i', $time);
        if (!in_array($timeStr, $booked)) {
            $slots[] = ['time' => $timeStr];
        }
    }

    echo json_encode($slots);
} catch (PDOException $e) {
    error_log('Kļūda ielādējot pieejamību: ' . $e->getMessage());
    sendError(500, 'Servera kļūda ielādējot laikus');
}