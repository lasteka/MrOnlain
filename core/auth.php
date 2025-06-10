<?php
// /core/auth.php - Autentifikācijas funkcijas

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Pārbauda vai administrators ir pieteicies
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Pārbauda vai lietotājs ir pieteicies
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Iegūst pašreizējā administratora informāciju
 */
function getCurrentAdmin() {
    global $pdo;
    
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT id, name, email, username FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getCurrentAdmin kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Iegūst pašreizējā lietotāja informāciju
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isUserLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getCurrentUser kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Administratora pieteikšanās
 */
function loginAdmin($email, $password) {
    global $pdo;
    
    try {
        validateEmail($email);
        
        // Meklē gan pēc email, gan username
        $stmt = $pdo->prepare('SELECT id, password_hash, name FROM admins WHERE email = ? OR username = ?');
        $stmt->execute([$email, $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            
            // Ģenerē un saglabā token
            $token = generateToken();
            $pdo->prepare('UPDATE admins SET token = ? WHERE id = ?')->execute([$token, $admin['id']]);
            
            error_log("Admin {$admin['name']} (ID: {$admin['id']}) veiksmīgi pieteicās");
            return ['success' => true, 'token' => $token, 'admin' => $admin];
        } else {
            error_log("Neveiksmīga admin pieteikšanās mēģinājums: $email");
            return ['success' => false, 'message' => 'Nepareizs e-pasts vai parole'];
        }
    } catch (Exception $e) {
        error_log('loginAdmin kļūda: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Servera kļūda'];
    }
}

/**
 * Lietotāja pieteikšanās
 */
function loginUser($email, $password) {
    global $pdo;
    
    try {
        validateEmail($email);
        
        $stmt = $pdo->prepare('SELECT id, password_hash, name, email, phone FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            
            // Ģenerē un saglabā token
            $token = generateToken();
            $pdo->prepare('UPDATE users SET token = ? WHERE id = ?')->execute([$token, $user['id']]);
            
            error_log("Lietotājs {$user['name']} (ID: {$user['id']}) veiksmīgi pieteicās");
            return [
                'success' => true, 
                'token' => $token, 
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone']
                ]
            ];
        } else {
            error_log("Neveiksmīga lietotāja pieteikšanās mēģinājums: $email");
            return ['success' => false, 'message' => 'Nepareizs e-pasts vai parole'];
        }
    } catch (Exception $e) {
        error_log('loginUser kļūda: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Servera kļūda'];
    }
}

/**
 * Lietotāja reģistrācija
 */
function registerUser($name, $email, $phone, $password) {
    global $pdo;
    
    try {
        // Validācija
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            return ['success' => false, 'message' => 'Visi lauki ir obligāti'];
        }
        
        validateEmail($email);
        validatePhone($phone);
        validatePassword($password);
        
        // Pārbauda vai e-pasts jau eksistē
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'E-pasts jau ir reģistrēts'];
        }
        
        // Pārbauda vai telefons jau eksistē
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Telefona numurs jau ir reģistrēts'];
        }
        
        // Izveido jaunu lietotāju
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $token = generateToken();
        
        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, token) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $phone, $passwordHash, $token]);
        
        $userId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        
        error_log("Jauns lietotājs reģistrēts: $name (ID: $userId, Email: $email)");
        
        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ]
        ];
        
    } catch (Exception $e) {
        error_log('registerUser kļūda: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Servera kļūda'];
    }
}

/**
 * Izrakstīšanās (gan admin, gan user)
 */
function logout() {
    try {
        global $pdo;
        
        // Noņem token no datubāzes
        if (isAdminLoggedIn()) {
            $pdo->prepare('UPDATE admins SET token = NULL WHERE id = ?')->execute([$_SESSION['admin_id']]);
            error_log("Admin ID {$_SESSION['admin_id']} izrakstījās");
            unset($_SESSION['admin_id']);
        }
        
        if (isUserLoggedIn()) {
            $pdo->prepare('UPDATE users SET token = NULL WHERE id = ?')->execute([$_SESSION['user_id']]);
            error_log("Lietotājs ID {$_SESSION['user_id']} izrakstījās");
            unset($_SESSION['user_id']);
        }
        
        // Notīra visas session vērtības
        session_destroy();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log('logout kļūda: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Kļūda izrakstīšanās laikā'];
    }
}

/**
 * Pārbauda vai lietotājs ir autentificēts pēc token
 */
function authenticateByToken($token) {
    global $pdo;
    
    if (empty($token)) {
        return false;
    }
    
    // Mēģina atrast lietotāju
    $user = getUserByToken($pdo, $token);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        return ['type' => 'user', 'data' => $user];
    }
    
    // Mēģina atrast admin
    $admin = getAdminByToken($pdo, $token);
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        return ['type' => 'admin', 'data' => $admin];
    }
    
    return false;
}

/**
 * Pārbauda admin piekļuvi
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        sendError(401, 'Nepieciešama administratora piekļuve');
    }
}

/**
 * Pārbauda lietotāja piekļuvi
 */
function requireUser() {
    if (!isUserLoggedIn()) {
        sendError(401, 'Nepieciešama autentifikācija');
    }
}

/**
 * Iegūst autentificēto lietotāju no headers vai session
 */
function getAuthenticatedUser() {
    // Pārbauda Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $auth = authenticateByToken($token);
        if ($auth && $auth['type'] === 'user') {
            return $auth['data'];
        }
    }
    
    // Pārbauda session
    if (isUserLoggedIn()) {
        return getCurrentUser();
    }
    
    return false;
}

/**
 * Iegūst autentificēto admin no headers vai session
 */
function getAuthenticatedAdmin() {
    // Pārbauda Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $auth = authenticateByToken($token);
        if ($auth && $auth['type'] === 'admin') {
            return $auth['data'];
        }
    }
    
    // Pārbauda session
    if (isAdminLoggedIn()) {
        return getCurrentAdmin();
    }
    
    return false;
}

// Pievieno šīs funkcijas tavā esošajā /core/auth.php failā (beigās)

/**
 * Uzlabota Authorization header iegūšana (bez .htaccess)
 */
function getAuthorizationHeader() {
    $token = null;
    
    // 1. Mēģina $_SERVER['HTTP_AUTHORIZATION']
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    
    // 2. Mēģina REDIRECT_HTTP_AUTHORIZATION (Apache rewrite)
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    
    // 3. Mēģina getallheaders() funkciju (ja pieejama)
    elseif (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = trim($headers['Authorization']);
        } elseif (isset($headers['authorization'])) {
            $token = trim($headers['authorization']);
        }
    }
    
    // 4. Mēģina meklēt jebkuru $_SERVER mainīgo ar 'authorization'
    if (!$token) {
        foreach ($_SERVER as $key => $value) {
            if (stripos($key, 'authorization') !== false && !empty($value)) {
                $token = trim($value);
                break;
            }
        }
    }
    
    // Debug log (tikai ja nav OPTIONS pieprasījums)
    if (!$token && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
        error_log('❌ Authorization header nav atrasts');
        error_log('Available $_SERVER headers: ' . print_r(array_filter($_SERVER, function($key) {
            return strpos($key, 'HTTP_') === 0 || stripos($key, 'auth') !== false;
        }, ARRAY_FILTER_USE_KEY), true));
    }
    
    return $token;
}

/**
 * Uzlabota admin autentifikācija (aizstāj esošo getAuthenticatedAdmin)
 */
function getAuthenticatedAdminImproved() {
    global $pdo;
    
    // Pārbauda Authorization header ar uzlaboto metodi
    $token = getAuthorizationHeader();
    
    if ($token && str_starts_with($token, 'Bearer ')) {
        $rawToken = substr($token, 7);
        $admin = getAdminByToken($pdo, $rawToken);
        if ($admin) {
            return $admin;
        }
    }
    
    // Pārbauda session kā fallback
    if (isAdminLoggedIn()) {
        return getCurrentAdmin();
    }
    
    return false;
}

/**
 * Uzlabota lietotāja autentifikācija (aizstāj esošo getAuthenticatedUser)
 */
function getAuthenticatedUserImproved() {
    global $pdo;
    
    // Pārbauda Authorization header ar uzlaboto metodi
    $token = getAuthorizationHeader();
    
    if ($token && str_starts_with($token, 'Bearer ')) {
        $rawToken = substr($token, 7);
        $user = getUserByToken($pdo, $rawToken);
        if ($user) {
            return $user;
        }
    }
    
    // Pārbauda session kā fallback
    if (isUserLoggedIn()) {
        return getCurrentUser();
    }
    
    return false;
}

/**
 * Vienkārša admin validācijas funkcija API failiem
 */
function validateAdminAuth() {
    $admin = getAuthenticatedAdminImproved();
    if (!$admin) {
        sendError(401, 'Nav autorizēts - nepieciešama admin piekļuve');
    }
    return $admin;
}

/**
 * Vienkārša lietotāja validācijas funkcija API failiem
 */
function validateUserAuth() {
    $user = getAuthenticatedUserImproved();
    if (!$user) {
        sendError(401, 'Nav autorizēts - nepieciešama autentifikācija');
    }
    return $user;
}
?>

