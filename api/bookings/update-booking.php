<?php
// /api/bookings/update-booking.php - uzlabota versija
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$token || !str_starts_with($token, 'Bearer ')) {
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7);
$user = getUserByToken($pdo, $rawToken);
$admin = getAdminByToken($pdo, $rawToken);

if (!$user && !$admin) {
    sendError(403, 'Nederīgs tokens');
}

// Iegūst datus
$id = $_POST['id'] ?? '';
$comment = trim($_POST['comment'] ?? '');
$date = $_POST['date'] ?? ''; // Pārcelšanai
$time = $_POST['time'] ?? ''; // Pārcelšanai
$service = $_POST['service'] ?? ''; // Pakalpojuma maiņai

try {
    // Validē ID
    if (!$id || !is_numeric($id)) {
        sendError(400, 'Nederīgs rezervācijas ID');
    }
    
    // Pārbauda vai rezervācija eksistē un pieder lietotājam
    $checkQuery = 'SELECT * FROM bookings WHERE id = ?';
    $checkParams = [$id];
    
    if ($user) {
        // Parastam lietotājam - tikai savas rezervācijas
        $checkQuery .= ' AND user_id = ?';
        $checkParams[] = $user['id'];
    }
    // Adminam nav ierobežojumu
    
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute($checkParams);
    $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError(404, 'Rezervācija nav atrasta vai jums nav tiesību to labot');
    }
    
    // Validē komentāru
    if (strlen($comment) > 500) {
        sendError(400, 'Komentārs nedrīkst būt garāks par 500 rakstzīmēm');
    }
    
    // Apstrādā bildes augšupielādi
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/uploads/';
        
        // Izveido uploads direktoriju, ja neeksistē
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $imageFile = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validē bildes tipu
        if (!in_array($imageFile['type'], $allowedTypes)) {
            sendError(400, 'Nederīgs bildes formāts. Atļauti: JPEG, PNG, GIF, WebP');
        }
        
        // Validē bildes izmēru
        if ($imageFile['size'] > $maxSize) {
            sendError(400, 'Bilde ir pārāk liela. Maksimālais izmērs: 5MB');
        }
        
        // Izdzēš veco bildi, ja tāda ir
        if ($booking['image'] && file_exists($uploadDir . $booking['image'])) {
            unlink($uploadDir . $booking['image']);
        }
        
        // Saglabā jauno bildi
        $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $imageName = 'booking_' . $id . '_' . time() . '.' . $extension;
        $imagePath = $uploadDir . $imageName;
        
        if (!move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
            error_log('Neizdevās saglabāt bildi: ' . $imagePath);
            sendError(500, 'Neizdevās saglabāt bildi');
        }
    }
    
    // Sagatavo UPDATE vaicājumu
    $updateFields = [];
    $updateParams = [];
    
    // Komentārs vienmēr tiek atjaunināts (var būt tukšs)
    $updateFields[] = 'comment = ?';
    $updateParams[] = $comment;
    
    // Bilde tikai ja augšupielādēta
    if ($imageName) {
        $updateFields[] = 'image = ?';
        $updateParams[] = $imageName;
    }
    
    // Pārcelšana - datums un laiks
    if ($date && $time) {
        // Validē datumu
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            sendError(400, 'Nederīgs datuma formāts');
        }
        
        // Pārbauda vai datums nav pagātnē
        $today = date('Y-m-d');
        if ($date < $today) {
            sendError(400, 'Nevar pārcelt uz pagātnes datumu');
        }
        
        // Validē laiku
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            sendError(400, 'Nederīgs laika formāts');
        }
        
        // Pārbauda vai jaunais laiks nav aizņemts (izņemot pašreizējo rezervāciju)
        $timeCheck = $pdo->prepare('SELECT id FROM bookings WHERE date = ? AND time = ? AND id != ?');
        $timeCheck->execute([$date, $time, $id]);
        if ($timeCheck->fetch()) {
            sendError(409, 'Šis laiks jau ir aizņemts. Izvēlieties citu laiku.');
        }
        
        $updateFields[] = 'date = ?';
        $updateParams[] = $date;
        $updateFields[] = 'time = ?';
        $updateParams[] = $time;
    }
    
    // Pakalpojuma maiņa
    if ($service && $service !== $booking['service']) {
        // Validē pakalpojumu
        $serviceCheck = $pdo->prepare('SELECT id FROM services WHERE name = ?');
        $serviceCheck->execute([$service]);
        if (!$serviceCheck->fetch()) {
            sendError(400, 'Nederīgs pakalpojums');
        }
        
        $updateFields[] = 'service = ?';
        $updateParams[] = $service;
    }
    
    // Pievieno WHERE nosacījumu
    if ($user) {
        $whereClause = 'id = ? AND user_id = ?';
        $updateParams[] = $id;
        $updateParams[] = $user['id'];
    } else {
        $whereClause = 'id = ?';
        $updateParams[] = $id;
    }
    
    // Izpilda UPDATE
    $updateQuery = 'UPDATE bookings SET ' . implode(', ', $updateFields) . ' WHERE ' . $whereClause;
    $stmt = $pdo->prepare($updateQuery);
    $success = $stmt->execute($updateParams);
    
    if (!$success || $stmt->rowCount() === 0) {
        sendError(400, 'Neizdevās atjaunināt rezervāciju');
    }
    
    // Debug log
    $userType = $user ? 'lietotājs' : 'admin';
    $changes = [];
    if ($comment !== $booking['comment']) $changes[] = 'komentārs';
    if ($imageName) $changes[] = 'bilde';
    if ($date && $date !== $booking['date']) $changes[] = 'datums';
    if ($time && $time !== $booking['time']) $changes[] = 'laiks';
    if ($service && $service !== $booking['service']) $changes[] = 'pakalpojums';
    
    error_log("Rezervācija atjaunināta ($userType): ID=$id, Izmaiņas: " . implode(', ', $changes));
    
    echo json_encode([
        'success' => true,
        'message' => 'Rezervācija veiksmīgi atjaunināta!',
        'updated_fields' => $changes
    ]);
    
} catch (PDOException $e) {
    error_log('Datubāzes kļūda atjauninot rezervāciju: ' . $e->getMessage());
    
    // Dzēš augšupielādēto bildi, ja kļūda
    if ($imageName && file_exists(__DIR__ . '/../../public/uploads/' . $imageName)) {
        unlink(__DIR__ . '/../../public/uploads/' . $imageName);
    }
    
    if ($e->getCode() == 23000) {
        sendError(409, 'Šis laiks jau ir aizņemts');
    } else {
        sendError(500, 'Datubāzes kļūda');
    }
} catch (Exception $e) {
    error_log('Vispārēja kļūda atjauninot rezervāciju: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>