<?php
// /api/admin/manage-hours.php - DEBUG versija ar uzlabotu validāciju
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

// DEBUG: Log incoming data
error_log("DEBUG: Action = $action");
error_log("DEBUG: POST data = " . print_r($_POST, true));

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

            // DEBUG: Log validation input
            error_log("DEBUG: Validating - Date: $date, Start: $start_time, End: $end_time, Available: $is_available");

            // Validācija
            if (!$date || !$start_time || !$end_time) {
                error_log("DEBUG: Empty fields validation failed");
                sendError(400, 'Datums, sākuma laiks un beigu laiks ir obligāti');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                error_log("DEBUG: Date format validation failed for: $date");
                sendError(400, 'Nederīgs datuma formāts');
            }

            if ($date < date('Y-m-d')) {
                error_log("DEBUG: Past date validation failed for: $date");
                sendError(400, 'Nevar pievienot darba laiku pagātnei');
            }

            // UZLABOTA laika validācija - atļauj gan "9:30", gan "09:30"
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
                error_log("DEBUG: Start time format validation failed for: $start_time");
                sendError(400, "Nederīgs sākuma laika formāts: $start_time");
            }
            
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                error_log("DEBUG: End time format validation failed for: $end_time");
                sendError(400, "Nederīgs beigu laika formāts: $end_time");
            }

            if ($start_time >= $end_time) {
                error_log("DEBUG: Time order validation failed: $start_time >= $end_time");
                sendError(400, 'Sākuma laikam jābūt pirms beigu laika');
            }

            error_log("DEBUG: All validations passed, inserting into DB");

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
                error_log("DEBUG: Database insert failed");
                sendError(500, 'Neizdevās saglabāt darba laiku');
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendError(405, 'Metode nav atļauta');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $date = $_POST['date'] ?? '';
            $start_time = $_POST['start_time'] ?? '';
            $end_time = $_POST['end_time'] ?? '';
            $is_available = isset($_POST['is_available']) && $_POST['is_available'] ? 1 : 0;

            // DEBUG: Log update data
            error_log("DEBUG: UPDATE - ID: $id, Date: $date, Start: $start_time, End: $end_time, Available: $is_available");

            // Validācija
            if (!$id) {
                sendError(400, 'Darba laika ID ir obligāts');
            }

            if (!$date || !$start_time || !$end_time) {
                sendError(400, 'Datums, sākuma laiks un beigu laiks ir obligāti');
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                sendError(400, 'Nederīgs datuma formāts');
            }

            // UZLABOTA laika validācija
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
                error_log("DEBUG: UPDATE Start time format validation failed for: $start_time");
                sendError(400, "Nederīgs sākuma laika formāts: $start_time");
            }
            
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
                error_log("DEBUG: UPDATE End time format validation failed for: $end_time");
                sendError(400, "Nederīgs beigu laika formāts: $end_time");
            }

            if ($start_time >= $end_time) {
                sendError(400, 'Sākuma laikam jābūt pirms beigu laika');
            }

            // Pārbauda vai darba laiks eksistē
            $stmt = $pdo->prepare('SELECT date FROM working_hours WHERE id = ?');
            $stmt->execute([$id]);
            $existingHour = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingHour) {
                sendError(404, 'Darba laiks nav atrasts');
            }

            // Pārbauda konfliktu ar citiem darba laikiem (izņemot pašreizējo)
            if ($date !== $existingHour['date']) {
                $stmt = $pdo->prepare('SELECT id FROM working_hours WHERE date = ? AND id != ?');
                $stmt->execute([$date, $id]);
                if ($stmt->fetch()) {
                    sendError(400, 'Šim datumam jau ir pievienots darba laiks');
                }
            }

            // Atjauno darba laiku
            $stmt = $pdo->prepare('
                UPDATE working_hours 
                SET date = ?, start_time = ?, end_time = ?, is_available = ? 
                WHERE id = ?
            ');
            
            $stmt->execute([$date, $start_time, $end_time, $is_available, $id]);
            
            if ($stmt->rowCount() > 0) {
                error_log("Admin atjaunoja darba laiku: ID=$id, Date=$date, $start_time-$end_time, Available=" . ($is_available ? 'yes' : 'no'));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Darba laiks atjaunots veiksmīgi'
                ]);
            } else {
                // Nav izmaiņu, bet tas nav kļūda
                echo json_encode([
                    'success' => true,
                    'message' => 'Darba laiks saglabāts (nav izmaiņu)'
                ]);
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