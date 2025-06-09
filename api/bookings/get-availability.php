<?php
// /api/bookings/get-availability.php - AR PAKALPOJUMA ILGUMA ŅEMŠANU VĒRĀ
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

$date = $_GET['date'] ?? '';
$serviceName = $_GET['service'] ?? ''; // Pakalpojuma nosaukums (nav obligāts)

if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    sendError(400, 'Nederīgs datuma formāts');
}

try {
    // Pārbauda vai datums nav pagātnē
    $today = date('Y-m-d');
    if ($date < $today) {
        echo json_encode([]);
        exit;
    }
    
    // Pārbauda vai nav svētdiena (0 = svētdiena)
    $dayOfWeek = date('w', strtotime($date));
    if ($dayOfWeek == 0) { // Svētdiena
        echo json_encode([]);
        exit;
    }
    
    // Iegūstam darba laikus no datubāzes
    $stmt = $pdo->prepare('SELECT start_time, end_time, is_available FROM working_hours WHERE date = ?');
    $stmt->execute([$date]);
    $hours = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ja nav ieraksta datubāzē, izmantos default darba laikus
    if (!$hours) {
        $defaultHours = [
            1 => ['start' => '09:00:00', 'end' => '18:00:00'], // Pirmdiena
            2 => ['start' => '09:00:00', 'end' => '18:00:00'], // Otrdiena
            3 => ['start' => '09:00:00', 'end' => '18:00:00'], // Trešdiena
            4 => ['start' => '09:00:00', 'end' => '18:00:00'], // Ceturtdiena
            5 => ['start' => '09:00:00', 'end' => '18:00:00'], // Piektdiena
            6 => ['start' => '10:00:00', 'end' => '16:00:00'], // Sestdiena
            0 => null // Svētdiena - nav darba laika
        ];
        
        if (!isset($defaultHours[$dayOfWeek]) || $defaultHours[$dayOfWeek] === null) {
            echo json_encode([]);
            exit;
        }
        
        $hours = [
            'start_time' => $defaultHours[$dayOfWeek]['start'],
            'end_time' => $defaultHours[$dayOfWeek]['end'],
            'is_available' => 1
        ];
    }

    // Pārbauda vai darba laiks ir pieejams
    if (!$hours['is_available']) {
        echo json_encode([]);
        exit;
    }

    // UZLABOTS: Iegūstam aizņemtos laikus AR pakalpojuma ilgumu
    $stmt = $pdo->prepare('
        SELECT 
            b.time,
            s.duration,
            TIME(b.time) as start_time,
            TIME(DATE_ADD(CONCAT(?, " ", b.time), INTERVAL COALESCE(s.duration, 60) MINUTE)) as end_time
        FROM bookings b
        LEFT JOIN services s ON b.service = s.name
        WHERE b.date = ?
    ');
    $stmt->execute([$date, $date]);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    error_log("DEBUG get-availability - Date: $date");
    error_log("DEBUG get-availability - Found " . count($bookedSlots) . " booked slots");
    foreach ($bookedSlots as $slot) {
        error_log("DEBUG get-availability - Booked: {$slot['start_time']} - {$slot['end_time']} (duration: {$slot['duration']}min)");
    }

    // Iegūstam izvēlētā pakalpojuma ilgumu (ja norādīts)
    $selectedServiceDuration = 60; // Default 60 minūtes
    if ($serviceName) {
        $stmt = $pdo->prepare('SELECT duration FROM services WHERE name = ?');
        $stmt->execute([$serviceName]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service) {
            $selectedServiceDuration = $service['duration'];
        }
    }

    // Funkcija lai pārbauda vai laika sloti pārklājas
    function timeSlotsOverlap($start1, $end1, $start2, $end2) {
        return ($start1 < $end2) && ($start2 < $end1);
    }

    // Ģenerējam pieejamos laikus
    $start = strtotime($hours['start_time']);
    $end = strtotime($hours['end_time']);
    $slots = [];
    $interval = 30 * 60; // 30 minūšu sloti

    for ($time = $start; $time < $end; $time += $interval) {
        $slotStartTime = date('H:i:s', $time);
        $slotEndTime = date('H:i:s', $time + ($selectedServiceDuration * 60));
        $slotDisplay = date('H:i', $time);
        
        // Pārbauda vai šis slots netraucē darba laiku
        $slotEndTimestamp = $time + ($selectedServiceDuration * 60);
        if ($slotEndTimestamp > strtotime($hours['end_time'])) {
            error_log("DEBUG get-availability - Slot $slotDisplay extends beyond work hours, skipping");
            continue;
        }
        
        // Pārbauda vai šis slots nepārklājas ar esošajām rezervācijām
        $isAvailable = true;
        foreach ($bookedSlots as $bookedSlot) {
            if (timeSlotsOverlap($slotStartTime, $slotEndTime, $bookedSlot['start_time'], $bookedSlot['end_time'])) {
                $isAvailable = false;
                error_log("DEBUG get-availability - Slot $slotDisplay conflicts with booking {$bookedSlot['start_time']}-{$bookedSlot['end_time']}, skipping");
                break;
            }
        }
        
        if ($isAvailable) {
            $slots[] = [
                'time' => $slotDisplay,
                'duration' => $selectedServiceDuration
            ];
        }
    }

    error_log("DEBUG get-availability - Generated " . count($slots) . " available slots for {$selectedServiceDuration}min service");
    
    echo json_encode($slots);
    
} catch (PDOException $e) {
    error_log('Kļūda ielādējot pieejamību: ' . $e->getMessage());
    sendError(500, 'Servera kļūda ielādējot laikus');
} catch (Exception $e) {
    error_log('Vispārēja kļūda get-availability: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}
?>