<?php
// /nails-booking/api/bookings/update-booking.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$id = $_POST['id'] ?? '';
$comment = trim($_POST['comment'] ?? '');

try {
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = 'uploads/' . uniqid() . '_' . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../public/' . $imagePath)) {
            sendError(500, 'Neizdevās augšupielādēt attēlu');
        }
    }

    $query = 'UPDATE bookings SET comment = ?, image = COALESCE(?, image)';
    $params = [$comment, $imagePath];
    if ($user) {
        $query .= ' WHERE id = ? AND user_id = ?';
        $params[] = $id;
        $params[] = $user['id'];
    } else {
        $query .= ' WHERE id = ?';
        $params[] = $id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount()) {
        echo json_encode(['success' => true]);
    } else {
        sendError(400, 'Neizdevās atjaunināt rezervāciju');
    }
} catch (PDOException $e) {
    error_log('Kļūda atjauninot rezervāciju: ' . $e->getMessage());
    sendError(500, 'Servera kļūda');
}