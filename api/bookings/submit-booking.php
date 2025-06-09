<?php
// /api/bookings/submit-booking.php - ar debug info
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

// DEBUG - visi authorization headers
$allHeaders = [];
foreach ($_SERVER as $key => $value) {
    if (stripos($key, 'auth') !== false || stripos($key, 'bearer') !== false || stripos($key, 'http') !== false) {
        $allHeaders[$key] = $value;
    }
}
error_log("DEBUG submit-booking - Visi iespējamie headers: " . json_encode($allHeaders));

// Uzlabota Authorization header iegūšana (tāda pati kā check-role.php)
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
error_log("DEBUG submit-booking - Token: " . ($token ? substr($token, 0, 20) . '...' : 'NAV'));

if (!$token || !str_starts_with($token, 'Bearer ')) {
    error_log("DEBUG submit-booking - Token validation failed");
    sendError(401, 'Nav autorizēts - token nav atrasts vai nav Bearer formātā');
}

$rawToken = substr($token, 7);
error_log("DEBUG submit-booking - Raw token: " . substr($rawToken, 0, 10) . "...");

// Debug token datubāzē
if (function_exists('debugTokenInDatabase')) {
    debugTokenInDatabase($pdo, $rawToken);
}

// Pārbauda vai funkcijas eksistē
if (!function_exists('getUserByToken')) {
    error_log("DEBUG submit-booking - getUserByToken funkcija neeksistē!");
    sendError(500, 'Servera konfigurācijas kļūda');
}

$user = getUserByToken($pdo, $rawToken);
$admin = getAdminByToken($pdo, $rawToken);

error_log("DEBUG submit-booking - User found: " . ($user ? 'jā (ID: ' . $user['id'] . ')' : 'nē'));
error_log("DEBUG submit-booking - Admin found: " . ($admin ? 'jā' : 'nē'));

if (!$user && !$admin) {
    error_log("DEBUG submit-booking - Ne user, ne admin nav atrasts");
    sendError(403, 'Nederīgs tokens - lietotājs nav atrasts');
}

// Pārbauda vai ir FormData (ar bildi) vai JSON
$isFormData = isset($_POST['date']);
error_log("DEBUG submit-booking - Is FormData: " . ($isFormData ? 'jā' : 'nē'));

if ($isFormData) {
    // FormData - ar bildi
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $service = trim($_POST['service'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    error_log("DEBUG submit-booking - FormData values: date=$date, time=$time, service=$service");
    
    // Apstrādā bildi
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        error_log("DEBUG submit-booking - Image upload detected");
        $uploadDir = __DIR__ . '/../../public/uploads/';
        
        // Izveido uploads direktoriju, ja neeksistē
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            error_log("DEBUG submit-booking - Created uploads directory");
        }
        
        $imageFile = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($imageFile['type'], $allowedTypes)) {
            $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
            $imageName = 'booking_' . ($user ? $user['id'] : 'admin') . '_' . time() . '.' . $extension;
            $imagePath = $uploadDir . $imageName;
            
            if (!move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                error_log('Neizdevās saglabāt bildi: ' . $imagePath);
                $imageName = null;
            } else {
                error_log("DEBUG submit-booking - Image saved: $imageName");
            }
        } else {
            error_log("DEBUG submit-booking - Invalid image type: " . $imageFile['type']);
            sendError(400, 'Nederīgs bildes formāts. Atļauti: JPEG, PNG, GIF, WebP');
        }
    }
} else {
    // JSON dati - bez bildes
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        error_log("DEBUG submit-booking - No JSON data received");
        sendError(400, 'Nav saņemti dati');
    }
    
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';
    $service = trim($data['service'] ?? '');
    $comment = trim($data['comment'] ?? '');
    $imageName = null;
    
    error_log("DEBUG submit-booking - JSON values: date=$date, time=$time, service=$service");
}

try {
    // Validācija
    if (!$date || !$time || !$service) {
        error_log("DEBUG submit-booking - Validation failed: missing required fields");
        sendError(400, 'Datums, laiks un pakalpojums ir obligāti');
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
        sendError(409, 'Šis laiks jau ir aizņemts. Lūdzu, izvēlieties citu laiku.');
    }
    
    // Validē komentāru
    if (strlen($comment) > 500) {
        sendError(400, 'Komentārs nedrīkst būt garāks par 500 rakstzīmēm');
    }

    // Izvēlas datus atkarībā no lietotāja tips
    $userId = $user ? $user['id'] : null;
    $userName = $user ? $user['name'] : ($data['name'] ?? '');
    $userPhone = $user ? $user['phone'] : ($data['phone'] ?? '');
    $userEmail = $user ? $user['email'] : null;
    
    error_log("DEBUG submit-booking - Ready to insert: userId=$userId, userName=$userName");
    
    // Ievieto rezervāciju ar bildi
    $stmt = $pdo->prepare('INSERT INTO bookings (user_id, name, phone, email, service, date, time, comment, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $success = $stmt->execute([$userId, $userName, $userPhone, $userEmail, $service, $date, $time, $comment, $imageName]);
    
    if (!$success) {
        throw new PDOException('Neizdevās saglabāt rezervāciju');
    }
    
    $bookingId = $pdo->lastInsertId();
    
    // Debug log
    $userType = $user ? 'reģistrēts' : 'admin';
    error_log("DEBUG submit-booking - SUCCESS: Jauna rezervācija ($userType): ID=$bookingId, User=$userName, Date=$date, Time=$time, Service=$service, Image=" . ($imageName ? 'Jā' : 'Nē'));
    
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'message' => 'Rezervācija veiksmīgi izveidota!'
    ]);
    
} catch (PDOException $e) {
    error_log('DEBUG submit-booking - PDO Error: ' . $e->getMessage());
    
    // Dzēš augšupielādēto bildi, ja kļūda
    if ($imageName && file_exists(__DIR__ . '/../../public/uploads/' . $imageName)) {
        unlink(__DIR__ . '/../../public/uploads/' . $imageName);
    }
    
    if ($e->getCode() == 23000) {
        sendError(409, 'Šis laiks jau ir aizņemts');
    } else {
        sendError(500, 'Kļūda veicot rezervāciju');
    }
} catch (Exception $e) {
    error_log('DEBUG submit-booking - General Error: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda');
}
?>