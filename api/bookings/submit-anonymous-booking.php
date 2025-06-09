<?php
// /api/bookings/submit-anonymous-booking.php - Nereģistrēto klientu rezervācijas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

try {
    // Iegūst JSON datus
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data)) {
        sendError(400, 'Nederīgs JSON formāts');
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $service = trim($data['service'] ?? '');
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';
    $comment = trim($data['comment'] ?? '');

    // Validācija
    if (!$name || !$phone || !$service || !$date || !$time) {
        sendError(400, 'Vārds, telefons, pakalpojums, datums un laiks ir obligāti');
    }

    // Validē telefonu
    if (!preg_match('/^\+?\d{8,}$/', $phone)) {
        sendError(400, 'Nederīgs telefona numurs');
    }

    // Validē datumu
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendError(400, 'Nederīgs datuma formāts');
    }

    // Pārbauda vai datums nav pagātnē
    $today = date('Y-m-d');
    if ($date < $today) {
        sendError(400, 'Nevar rezervēt pagātnes datumu');
    }

    // Validē laiku
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        sendError(400, 'Nederīgs laika formāts');
    }

    // Pārbauda vai laiks nav jau aizņemts
    $timeCheck = $pdo->prepare('SELECT id FROM bookings WHERE date = ? AND time = ?');
    $timeCheck->execute([$date, $time]);
    if ($timeCheck->fetch()) {
        sendError(409, 'Šis laiks jau ir aizņemts. Izvēlieties citu laiku.');
    }

    // Validē komentāru
    if (strlen($comment) > 500) {
        sendError(400, 'Komentārs nedrīkst būt garāks par 500 rakstzīmēm');
    }

    // Ievieto rezervāciju (bez user_id - anonīma rezervācija)
    $stmt = $pdo->prepare('
        INSERT INTO bookings (user_id, name, phone, email, service, date, time, comment, created_at) 
        VALUES (NULL, ?, ?, NULL, ?, ?, ?, ?, NOW())
    ');
    
    $success = $stmt->execute([$name, $phone, $service, $date, $time, $comment]);
    
    if (!$success) {
        throw new PDOException('Neizdevās saglabāt rezervāciju');
    }
    
    $bookingId = $pdo->lastInsertId();
    
    // Log
    error_log("Anonīma rezervācija izveidota: ID=$bookingId, Name=$name, Phone=$phone, Date=$date, Time=$time, Service=$service");
    
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'message' => 'Rezervācija veiksmīgi izveidota!'
    ]);
    
} catch (PDOException $e) {
    error_log('Anonīmās rezervācijas kļūda: ' . $e->getMessage());
    
    if ($e->getCode() == 23000) {
        sendError(409, 'Šis laiks jau ir aizņemts');
    } else {
        sendError(500, 'Kļūda veicot rezervāciju');
    }
} catch (Exception $e) {
    error_log('Vispārēja anonīmās rezervācijas kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>