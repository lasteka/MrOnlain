<?php
// nails-booking/api/bookings/get-services.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!file_exists(__DIR__ . '/../../core/db.php') || !file_exists(__DIR__ . '/../../core/functions.php')) {
    error_log('Kļūda: Trūkst core/db.php vai core/functions.php');
    http_response_code(500);
    echo json_encode(['error' => 'Servera konfigurācijas kļūda: Trūkst nepieciešamo failu']);
    exit;
}

try {
    require_once __DIR__ . '/../../core/db.php';
    require_once __DIR__ . '/../../core/functions.php';

    if (!$pdo) {
        throw new Exception('Datubāzes savienojums nav izveidots');
    }

    $stmt = $pdo->query('SELECT id, name, price, duration FROM services');
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['services' => $services]);
} catch (PDOException $e) {
    error_log('Datubāzes kļūda ielādējot pakalpojumus: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Datubāzes kļūda: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log('Vispārēja kļūda ielādējot pakalpojumus: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Servera kļūda: ' . $e->getMessage()]);
    exit;
}