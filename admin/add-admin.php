<?php
// /admin/add-admin.php

require_once '../core/db.php';
require_once '../core/auth.php';

$email = 'admin@gmail.com'; // būs kā username
$password = 'admin123';

try {
    // Pārbaudām vai administrators ar šādu lietotājvārdu jau eksistē
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Kļūda: Administrators ar šādu lietotājvārdu jau eksistē!");
    }

    // Šifrējam paroli
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Pievienojam administratoru
    $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
    if ($stmt->execute([$email, $passwordHash])) {
        echo "✅ Administrators veiksmīgi izveidots!<br>";
        echo "📧 Lietotājvārds: <strong>$email</strong><br>";
        echo "🔐 Parole: <strong>admin123</strong><br>";
        echo "<a href='login.php'>Ielogoties</a>";
    } else {
        echo "❌ Kļūda pievienojot administratoru.";
    }
} catch (PDOException $e) {
    die("Kļūda: " . $e->getMessage());
}