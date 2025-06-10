<?php
// /api/admin/manage-clients.php - Klientu pārvaldība admin panelim
header('Content-Type: application/json; charset=utf-8');
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
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendError(405, 'Metode nav atļauta');
            }
            
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? 'temp123';

            // Validācija
            if (!$name) {
                sendError(400, 'Vārds ir obligāts');
            }
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendError(400, 'Nederīgs e-pasta formāts');
            }
            
            if (!$phone) {
                sendError(400, 'Telefons ir obligāts');
            }

            // Pārbauda dublējošos ierakstus
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR phone = ?');
            $stmt->execute([$email, $phone]);
            if ($stmt->fetch()) {
                sendError(400, 'Klients ar šādu e-pastu vai telefonu jau eksistē');
            }

            // Izveido jaunu klientu
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $clientToken = generateToken();
            
            $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, token) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $phone, $passwordHash, $clientToken]);
            
            $newId = $pdo->lastInsertId();
            error_log("Admin pievienoja klientu: ID=$newId, Name=$name, Email=$email");

            echo json_encode([
                'success' => true,
                'message' => 'Klients pievienots veiksmīgi',
                'id' => $newId
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendError(405, 'Metode nav atļauta');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (!$id) {
                sendError(400, 'Klienta ID ir obligāts');
            }
            
            // Validācija
            if (!$name) {
                sendError(400, 'Vārds ir obligāts');
            }
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendError(400, 'Nederīgs e-pasta formāts');
            }
            
            if (!$phone) {
                sendError(400, 'Telefons ir obligāts');
            }

            // Pārbauda vai klients eksistē
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                sendError(404, 'Klients nav atrasts');
            }

            // Pārbauda dublējošos ierakstus (izņemot pašreizējo)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?');
            $stmt->execute([$email, $phone, $id]);
            if ($stmt->fetch()) {
                sendError(400, 'Klients ar šādu e-pastu vai telefonu jau eksistē');
            }

            // Atjauno klientu
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, $id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Admin atjaunoja klientu: ID=$id, Name=$name, Email=$email");
                echo json_encode([
                    'success' => true,
                    'message' => 'Klients atjaunots veiksmīgi'
                ]);
            } else {
                sendError(500, 'Neizdevās atjaunot klientu');
            }
            break;
            
        case 'delete':
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                sendError(400, 'Klienta ID ir obligāts');
            }

            // Pārbauda vai klients eksistē
            $stmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$client) {
                sendError(404, 'Klients nav atrasts');
            }

            // Sāk transakciju, lai dzēstu gan klientu, gan viņa rezervācijas
            $pdo->beginTransaction();
            
            try {
                // Dzēš klienta rezervācijas
                $stmt = $pdo->prepare('DELETE FROM bookings WHERE user_id = ?');
                $stmt->execute([$id]);
                $deletedBookings = $stmt->rowCount();
                
                // Dzēš klientu
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    $pdo->commit();
                    error_log("Admin izdzēsa klientu: ID=$id, Name={$client['name']}, Rezervācijas dzēstas: $deletedBookings");
                    echo json_encode([
                        'success' => true,
                        'message' => 'Klients un viņa rezervācijas dzēstas veiksmīgi'
                    ]);
                } else {
                    $pdo->rollback();
                    sendError(500, 'Neizdevās dzēst klientu');
                }
            } catch (Exception $e) {
                $pdo->rollback();
                error_log('Kļūda dzēšot klientu: ' . $e->getMessage());
                sendError(500, 'Datubāzes kļūda dzēšot klientu');
            }
            break;
            
        default:
            sendError(400, 'Nezināma darbība: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log('Admin klientu DB kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Admin klientu kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda: ' . $e->getMessage());
}
?>