<?php
// /api/bookings/delete-booking.php - IZLABOTS ar pareizo CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// Uzlabota Authorization header iegūšana
function getAuthorizationHeader() {
    $headers = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    return $headers;
}

$token = getAuthorizationHeader();
if (!$token || !str_starts_with($token, 'Bearer ')) {
    sendError(401, 'Nav autorizēts');
}

$rawToken = substr($token, 7);
$user = getUserByToken($pdo, $rawToken);
$admin = getAdminByToken($pdo, $rawToken);

if (!$user && !$admin) {
    sendError(403, 'Nederīgs tokens');
}

$id = $_GET['id'] ?? '';

try {
    // Validē ID
    if (!$id || !is_numeric($id)) {
        sendError(400, 'Nederīgs rezervācijas ID');
    }
    
    // Pārbauda vai rezervācija eksistē un iegūst informāciju par to
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
        sendError(404, 'Rezervācija nav atrasta vai jums nav tiesību to dzēst');
    }
    
    // IZŅEMTS: Laika ierobežojumi dzēšanai (lai testēšana būtu vieglāka)
    // Produktīvajā vidē varētu pievienot atpakaļ:
    /*
    $bookingDateTime = $booking['date'] . ' ' . $booking['time'];
    $now = date('Y-m-d H:i');
    
    if ($user && $bookingDateTime < $now) {
        sendError(400, 'Nevar dzēst pagātnes rezervāciju');
    }
    
    if ($user) {
        $cancelDeadline = date('Y-m-d H:i', strtotime($bookingDateTime . ' -24 hours'));
        if ($now > $cancelDeadline) {
            sendError(400, 'Rezervāciju var atcelt vismaz 24 stundas iepriekš');
        }
    }
    */
    
    // Dzēš piesaistīto bildi, ja tāda ir
    if ($booking['image']) {
        $imagePath = __DIR__ . '/../../public/uploads/' . $booking['image'];
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                error_log("Dzēsta bilde: {$booking['image']}");
            } else {
                error_log("Neizdevās dzēst bildi: {$booking['image']}");
            }
        }
    }
    
    // Dzēš rezervāciju
    $deleteQuery = 'DELETE FROM bookings WHERE id = ?';
    $deleteParams = [$id];
    
    if ($user) {
        $deleteQuery .= ' AND user_id = ?';
        $deleteParams[] = $user['id'];
    }
    
    $stmt = $pdo->prepare($deleteQuery);
    $success = $stmt->execute($deleteParams);
    
    if (!$success || $stmt->rowCount() === 0) {
        sendError(500, 'Neizdevās dzēst rezervāciju');
    }
    
    // Debug log
    $userType = $user ? 'lietotājs' : 'admin';
    $userName = $user ? $user['name'] : 'admin';
    error_log("Rezervācija dzēsta ($userType $userName): ID=$id, Datums={$booking['date']}, Laiks={$booking['time']}, Pakalpojums={$booking['service']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Rezervācija veiksmīgi atcelta!',
        'deleted_booking' => [
            'id' => $id,
            'date' => $booking['date'],
            'time' => $booking['time'],
            'service' => $booking['service']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Datubāzes kļūda dzēšot rezervāciju: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda');
} catch (Exception $e) {
    error_log('Vispārēja kļūda dzēšot rezervāciju: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>