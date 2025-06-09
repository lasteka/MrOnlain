<?php
// /api/auth/register.php - IZLABOTS ar pareizo CORS
header('Content-Type: application/json; charset=utf-8');

// IZLABOTS: Atļaut gan localhost, gan 127.0.0.1
$allowedOrigins = [
    'http://localhost', 
    'http://127.0.0.1',
    'http://localhost:80',
    'http://127.0.0.1:80'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    // Ja nav atrasts konkrētais origin, atļaut localhost
    header('Access-Control-Allow-Origin: http://localhost');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/functions.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data)) {
        sendError(400, 'Nederīgs JSON formāts');
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$name || !$phone || !$email || !$password) {
        sendError(400, 'Visi lauki ir obligāti');
    }

    // Validācija ar izņēmumu noķeršanu
    try {
        validateEmail($email);
        validatePhone($phone);
        validatePassword($password);
    } catch (Exception $e) {
        sendError(400, $e->getMessage());
    }

    // Pārbauda, vai e-pasts jau eksistē
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError(400, 'Šis e-pasts jau ir reģistrēts');
    }

    // Pārbauda, vai telefons jau eksistē
    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        sendError(400, 'Šis telefona numurs jau ir reģistrēts');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $token = generateToken();

    $stmt = $pdo->prepare('INSERT INTO users (name, phone, email, password_hash, token) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $email, $passwordHash, $token]);

    $userId = $pdo->lastInsertId();

    // Startē session
    session_start();
    $_SESSION['user_id'] = $userId;

    // Atgriež veiksmīgo atbildi
    echo json_encode([
        'success' => true,
        'token' => $token,
        'role' => 'client',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone
        ]
    ]);

} catch (PDOException $e) {
    error_log('Reģistrācijas kļūda: ' . $e->getMessage());
    sendError(500, 'Datubāzes kļūda: ' . $e->getMessage());
} catch (Exception $e) {
    error_log('Vispārēja kļūda: ' . $e->getMessage());
    sendError(500, 'Servera kļūda: ' . $e->getMessage());
}
?>