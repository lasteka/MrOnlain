<?php
// nails-booking/core/db.php - XAMPP socket versija
try {
    $pdo = new PDO('mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=nail_studio;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
    ]);
} catch (PDOException $e) {
    error_log('Datubāzes savienojuma kļūda: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Datubāzes savienojuma kļūda']);
    exit;
}
