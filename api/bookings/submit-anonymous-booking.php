<?php
// /nails-booking/api/bookings/submit-anonymous-booking.php - uzlabota versija
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Pārbauda vai saņemts JSON
$input = file_get_contents('php://input');
if (empty($input)) {
    sendError(400, 'Nav saņemti dati');
}

$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError(400, 'Nederīgs JSON formāts');
}

// Iegūst un validē datus
$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$service = trim($data['service'] ?? '');
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$comment = trim($data['comment'] ?? '');

try {
    // Validē obligātos laukus
    if (!$name || !$phone || !$service || !$date || !$time) {
        sendError(400, 'Visi lauki ir obligāti');
    }
    
    // Validē vārdu
    if (strlen($name) < 2 || strlen($name) > 100) {
        sendError(400, 'Vārdam jābūt no 2 līdz 100 rakstzīmēm');
    }
    
    // Validē telefonu
    if (!preg_match('/^\+?\d{8,15}$/', $phone)) {
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
    
    // Validē pakalpojumu - pārbauda vai eksistē
    $serviceCheck = $pdo->prepare('SELECT id FROM services WHERE name = ?');
    $serviceCheck->execute([$service]);
    if (!$serviceCheck->fetch()) {
        sendError(400, 'Nederīgs pakalpojums');
    }
    
    // Pārbauda vai laiks nav jau aizņemts
    $timeCheck = $pdo->prepare('SELECT id FROM bookings WHERE date = ? AND time = ?');
    $timeCheck->execute([$date, $time]);
    if ($timeCheck->fetch()) {
        sendError(409, 'Šis laiks jau ir aizņemts. Lūdzu, izvēlieties citu laiku.');
    }
    
    // Pārbauda vai darba laiks ir pieejams
    $workCheck = $pdo->prepare('SELECT is_available FROM working_hours WHERE date = ?');
    $workCheck->execute([$date]);
    $workDay = $workCheck->fetch();
    
    // Ja nav ieraksta working_hours, pieņem ka ir pieejams (saskaņā ar default loģiku)
    if ($workDay && !$workDay['is_available']) {
        sendError(400, 'Šis datums nav pieejams rezervācijām');
    }
    
    // Validē komentāru (ja ir)
    if (strlen($comment) > 500) {
        sendError(400, 'Komentārs nedrīkst būt garāks par 500 rakstzīmēm');
    }
    
    // Ievieto rezervāciju
    $stmt = $pdo->prepare('INSERT INTO bookings (name, phone, service, date, time, comment, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $success = $stmt->execute([$name, $phone, $service, $date, $time, $comment]);
    
    if (!$success) {
        throw new PDOException('Neizdevās saglabāt rezervāciju');
    }
    
    $bookingId = $pdo->lastInsertId();
    
    // Debug log (var izņemt production vidē)
    error_log("Jauna anonīmā rezervācija: ID=$bookingId, Name=$name, Date=$date, Time=$time, Service=$service");
    
    // Atgriež veiksmes ziņojumu
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'message' => 'Rezervācija veiksmīgi izveidota!'
    ]);
    
} catch (PDOException $e) {
    error_log('Datubāzes kļūda rezervācijā: ' . $e->getMessage());
    
    // Pārbauda vai ir duplicate entry kļūda
    if ($e->getCode() == 23000) {
        sendError(409, 'Šis laiks jau ir aizņemts. Lūdzu, izvēlieties citu laiku.');
    } else {
        sendError(500, 'Kļūda veicot rezervāciju. Lūdzu, mēģiniet vēlāk.');
    }
} catch (Exception $e) {
    error_log('Vispārēja kļūda rezervācijā: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda. Lūdzu, mēģiniet vēlāk.');
}
?>