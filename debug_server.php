<?php
// /debug_server.php - Servera un token diagnostika
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/core/db.php';
    require_once __DIR__ . '/core/functions.php';
    
    echo json_encode(['status' => 'success', 'message' => 'Database connection OK']);
    
    // Pārbauda admin tabulu
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    $adminTableExists = $stmt->rowCount() > 0;
    
    if ($adminTableExists) {
        // Parāda admin datus
        $stmt = $pdo->query("SELECT id, name, email, username, LEFT(token, 10) as token_start, created_at FROM admins");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n\n=== ADMIN TABULA ===\n";
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}\n";
            echo "Token start: {$admin['token_start']}..., Created: {$admin['created_at']}\n";
        }
    } else {
        echo "\n❌ Admin tabula neeksistē!";
    }
    
    // Pārbauda token
    $testToken = 'fade51796c';
    $stmt = $pdo->prepare("SELECT id, name FROM admins WHERE token LIKE ?");
    $stmt->execute([$testToken . '%']);
    $adminByToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n\n=== TOKEN PĀRBAUDE ===\n";
    echo "Meklētais token starts: $testToken\n";
    echo "Atrasts admin: " . ($adminByToken ? json_encode($adminByToken) : 'NAV') . "\n";
    
    // Headers debug
    echo "\n\n=== HEADERS DEBUG ===\n";
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0 || stripos($key, 'auth') !== false) {
            echo "$key: $value\n";
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>