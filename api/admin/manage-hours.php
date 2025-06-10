<?php
// /api/admin/manage-hours.php - UZLABOTS ar visām funkcijām
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

// Admin autentifikācija (tāda pati kā manage-services.php)
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
        case 'get':
            // Iegūst darba laikus
            $stmt = $pdo->query('
                SELECT 
                    id, 
                    date, 
                    start_time, 
                    end_time, 
                    is_available,
                    created_at
                FROM working_hours 
                ORDER BY date DESC, start_time ASC
            ');
            $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatē rezultātus
            $formattedHours = [];
            foreach ($hours as $hour) {
                $formattedHours[] = [
                    'id' => intval($hour['id']),
                    'date' => $hour['date'],
                    'start_time' => $hour['start_time'],
                    'end_time' => $hour['end_time'],
                    'is_available' => boolval($hour['is_available']),
                    'created_at' => $hour['created_at'],
                    'formatted_date' => date('d.m.Y', strtotime($hour['date']))
                ];
            }
            
            error_log("Admin ielādēja " . count($formattedHours) . " darba laikus");
            echo json_encode($formattedHours);
            break;
  
  case 'add':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError(405, 'Metode nav atļauta');
    }
    
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $is_available = isset($_POST['is_available']) && $_POST['is_available'] ? 1 : 0;

    // Validācija
    if (!$date || !$start_time || !$end_time) {
        sendError(400, 'Datums, sākuma laiks un beigu laiks ir obligāti');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        sendError(400, 'Nederīgs datuma formāts');
    }

    if ($date < date('Y-m-d')) {
        sendError(400, 'Nevar pievienot darba laiku pagātnei');
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
        sendError(400, 'Nederīgs laika formāts');
    }

    if ($start_time >= $end_time) {
        sendError(400, 'Sākuma laikam jābūt pirms beigu laika');
    }

    // UPSERT - atjauno, ja eksistē, pievieno, ja nav
    $stmt = $pdo->prepare('
        INSERT INTO working_hours (date, start_time, end_time, is_available) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        start_time = VALUES(start_time),
        end_time = VALUES(end_time),
        is_available = VALUES(is_available)
    ');
    
    if ($stmt->execute([$date, $start_time, $end_time, $is_available])) {
        $newId = $pdo->lastInsertId();
        $message = $newId > 0 ? 'Darba laiks pievienots veiksmīgi' : 'Darba laiks atjaunots veiksmīgi';
        
        error_log("Admin saglabāja darba laiku: Date=$date, $start_time-$end_time, Available=" . ($is_available ? 'yes' : 'no'));
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'id' => $newId ?: 'updated'
        ]);
    } else {
        sendError(500, 'Neizdevās saglabāt darba laiku');
    }
    break;

            // Mēģina pievienot darba laiku
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO working_hours (date, start_time, end_time, is_available) 
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$date, $start_time, $end_time, $is_available]);
                
                $newId = $pdo->lastInsertId();
                $statusText = $is_available ? 'pieejams' : 'nepieejams';
                
                error_log("Admin pievienoja darba laiku: ID=$newId, Date=$date, $start_time-$end_time ($statusText)");

                echo json_encode([
                    'success' => true,
                    'message' => 'Darba laiks pievienots veiksmīgi',
                    'id' => $newId
                ]);
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
                    sendError(400, 'Darba laiks šim datumam jau eksistē');
                } else {
                    error_log('DB kļūda pievienojot darba laiku: ' . $e->getMessage());
                    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
                }
            }
            break;
            
        case 'delete':
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                sendError(400, 'Darba laika ID ir obligāts');
            }

            // Pārbauda vai darba laiks eksistē
            $stmt = $pdo->prepare('SELECT date FROM working_hours WHERE id = ?');
            $stmt->execute([$id]);
            $workingHour = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$workingHour) {
                sendError(404, 'Darba laiks nav atrasts');
            }

            // Pārbauda rezervācijas
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM bookings WHERE date = ?');
            $stmt->execute([$workingHour['date']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                sendError(400, 'Nevar dzēst darba laiku, jo šajā datumā ir rezervācijas');
            }

            // Dzēš darba laiku
            $stmt = $pdo->prepare('DELETE FROM working_hours WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                error_log("Admin izdzēsa darba laiku: ID=$id, Date={$workingHour['date']}");
                echo json_encode([
                    'success' => true,
                    'message' => 'Darba laiks dzēsts veiksmīgi'
                ]);
            } else {
                sendError(500, 'Neizdevās dzēst darba laiku');
            }
            break;
            
        default:
            sendError(400, 'Nezināma darbība: ' . $action);
    }
    
} catch (PDOException $e) {
    error_log('Admin darba laiku DB kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Admin darba laiku kļūda: ' . $e->getMessage());
    sendError(500, 'Sistēmas kļūda: ' . $e->getMessage());
}
?>