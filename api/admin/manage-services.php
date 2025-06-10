<?php
// /api/admin/manage-services.php - UZLABOTS ar pakalpojumu atjaunošanas funkciju
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
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
            $price = floatval($_POST['price'] ?? 0);
            $duration = intval($_POST['duration'] ?? 0);

            if (!$name) {
                sendError(400, 'Pakalpojuma nosaukums ir obligāts');
            }
            
            if ($price <= 0) {
                sendError(400, 'Cena jābūt lielākai par 0');
            }
            
            if ($duration <= 0) {
                sendError(400, 'Ilgums jābūt lielākam par 0');
            }

            // Izmanto INSERT ar UNIQUE constraint
            try {
                $stmt = $pdo->prepare('INSERT INTO services (name, price, duration) VALUES (?, ?, ?)');
                $stmt->execute([$name, $price, $duration]);
                
                $newId = $pdo->lastInsertId();
                error_log("Admin pievienoja pakalpojumu: ID=$newId, Name=$name, Price=$price, Duration=$duration");

                echo json_encode([
                    'success' => true,
                    'message' => 'Pakalpojums pievienots veiksmīgi',
                    'id' => $newId
                ]);
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    error_log("Dublējošs pakalpojums: $name (Admin: {$admin['username']})");
                    sendError(400, 'Pakalpojums ar šādu nosaukumu jau eksistē');
                } else {
                    error_log('DB kļūda pievienojot pakalpojumu: ' . $e->getMessage());
                    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
                }
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendError(405, 'Metode nav atļauta');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $duration = intval($_POST['duration'] ?? 0);

            if (!$id) {
                sendError(400, 'Pakalpojuma ID ir obligāts');
            }

            if (!$name) {
                sendError(400, 'Pakalpojuma nosaukums ir obligāts');
            }
            
            if ($price <= 0) {
                sendError(400, 'Cena jābūt lielākai par 0');
            }
            
            if ($duration <= 0) {
                sendError(400, 'Ilgums jābūt lielākam par 0');
            }

            // Pārbauda vai pakalpojums eksistē
            $stmt = $pdo->prepare('SELECT name FROM services WHERE id = ?');
            $stmt->execute([$id]);
            $oldService = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldService) {
                sendError(404, 'Pakalpojums nav atrasts');
            }

            // Pārbauda dublējošos nosaukumus (izņemot pašreizējo)
            $stmt = $pdo->prepare('SELECT id FROM services WHERE name = ? AND id != ?');
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                sendError(400, 'Pakalpojums ar šādu nosaukumu jau eksistē');
            }

            // Atjauno pakalpojumu
            $stmt = $pdo->prepare('UPDATE services SET name = ?, price = ?, duration = ? WHERE id = ?');
            $stmt->execute([$name, $price, $duration, $id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Admin atjaunoja pakalpojumu: ID=$id, OldName={$oldService['name']}, NewName=$name, Price=$price, Duration=$duration");
                
                // Atjauno arī rezervāciju tabulu, ja nosaukums mainījās
                if ($oldService['name'] !== $name) {
                    $stmt = $pdo->prepare('UPDATE bookings SET service = ? WHERE service = ?');
                    $stmt->execute([$name, $oldService['name']]);
                    $updatedBookings = $stmt->rowCount();
                    error_log("Atjaunoja $updatedBookings rezervācijas ar jauno pakalpojuma nosaukumu");
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Pakalpojums atjaunots veiksmīgi'
                ]);
            } else {
                // Nav izmaiņu, bet tas nav kļūda
                echo json_encode([
                    'success' => true,
                    'message' => 'Pakalpojums saglabāts (nav izmaiņu)'
                ]);
            }
            break;
            
        case 'delete':
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                sendError(400, 'Pakalpojuma ID ir obligāts');
            }

            // Pārbauda vai pakalpojums eksistē
            $stmt = $pdo->prepare('SELECT name FROM services WHERE id = ?');
            $stmt->execute([$id]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                sendError(404, 'Pakalpojums nav atrasts');
            }

            // Pārbauda aktīvas rezervācijas
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE service = ? AND date >= CURDATE()');
            $stmt->execute([$service['name']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                sendError(400, 'Nevar dzēst pakalpojumu, jo tam ir aktīvas rezervācijas');
            }

            // Dzēš pakalpojumu
            $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                error_log("Admin izdzēsa pakalpojumu: ID=$id, Name={$service['name']}");
                echo json_encode([
                    'success' => true,
                    'message' => 'Pakalpojums dzēsts veiksmīgi'
                ]);
            } else {
                sendError(500, 'Neizdevās dzēst pakalpojumu');
            }
            break;
            
        default:
            sendError(400, 'Nezināma darbība: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log('Admin pakalpojumu DB kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Admin pakalpojumu kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda: ' . $e->getMessage());
}
?>