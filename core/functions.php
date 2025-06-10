<?php
// /core/functions.php - Pamata funkcijas rezervāciju sistēmai

/**
 * Nosūta JSON kļūdas ziņojumu
 */
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ]);
    exit;
}

/**
 * Nosūta JSON panākuma ziņojumu
 */
function sendSuccess($message, $data = null) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Ģenerē drošu autentifikācijas token
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validē e-pasta adresi
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Nederīgs e-pasta formāts');
    }
    return true;
}

/**
 * Validē telefona numuru
 */
function validatePhone($phone) {
    // Atļauj +371, 371, vai sākas ar 2
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    
    if (strlen($cleaned) < 8) {
        throw new Exception('Telefona numurs ir pārāk īss');
    }
    
    if (!preg_match('/^(\+371|371|2)/', $cleaned)) {
        throw new Exception('Nederīgs telefona numura formāts');
    }
    
    return true;
}

/**
 * Validē paroli
 */
function validatePassword($password) {
    if (strlen($password) < 6) {
        throw new Exception('Parolei jābūt vismaz 6 simbolus garai');
    }
    return true;
}

/**
 * Iegūst lietotāju pēc token
 */
function getUserByToken($pdo, $token) {
    try {
        $stmt = $pdo->prepare('SELECT id, name, email, phone FROM users WHERE token = ?');
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getUserByToken kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Iegūst administratoru pēc token
 */
function getAdminByToken($pdo, $token) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, email, name FROM admins WHERE token = ?');
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getAdminByToken kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Validē datumu
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        throw new Exception('Nederīgs datuma formāts');
    }
    
    $today = new DateTime();
    if ($d < $today->setTime(0, 0, 0)) {
        throw new Exception('Nevar rezervēt pagātnē');
    }
    
    return true;
}

/**
 * Validē laiku
 */
function validateTime($time) {
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
        throw new Exception('Nederīgs laika formāts');
    }
    return true;
}

/**
 * Formatē datumu latviešu valodā
 */
function formatDateLV($date) {
    $months = [
        1 => 'janvāris', 2 => 'februāris', 3 => 'marts', 4 => 'aprīlis',
        5 => 'maijs', 6 => 'jūnijs', 7 => 'jūlijs', 8 => 'augusts',
        9 => 'septembris', 10 => 'oktobris', 11 => 'novembris', 12 => 'decembris'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day. $month $year";
}

/**
 * Formatē laiku 12h formātā
 */
function formatTime12h($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Pārbauda vai datums ir darba diena
 */
function isWorkingDay($pdo, $date) {
    try {
        $stmt = $pdo->prepare('SELECT is_available FROM working_hours WHERE date = ?');
        $stmt->execute([$date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['is_available'] : false;
    } catch (PDOException $e) {
        error_log('isWorkingDay kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Iegūst pieejamos laikus konkrētam datumam
 */
function getAvailableSlots($pdo, $date) {
    try {
        // Iegūst darba laikus
        $stmt = $pdo->prepare('
            SELECT start_time, end_time 
            FROM working_hours 
            WHERE date = ? AND is_available = 1
        ');
        $stmt->execute([$date]);
        $workingHours = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$workingHours) {
            return [];
        }
        
        // Iegūst aizņemtos laikus
        $stmt = $pdo->prepare('
            SELECT time, s.duration 
            FROM bookings b
            LEFT JOIN services s ON b.service = s.name
            WHERE b.date = ? AND b.status != "cancelled"
        ');
        $stmt->execute([$date]);
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ģenerē pieejamos laika slotus (30 min intervālos)
        $start = strtotime($workingHours['start_time']);
        $end = strtotime($workingHours['end_time']);
        $slots = [];
        
        for ($time = $start; $time < $end; $time += 30 * 60) {
            $slot = date('H:i', $time);
            $isAvailable = true;
            
            // Pārbauda vai laiks nav aizņemts
            foreach ($bookedSlots as $booked) {
                $bookedStart = strtotime($booked['time']);
                $bookedEnd = $bookedStart + ($booked['duration'] * 60);
                
                if ($time >= $bookedStart && $time < $bookedEnd) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $slots[] = $slot;
            }
        }
        
        return $slots;
    } catch (PDOException $e) {
        error_log('getAvailableSlots kļūda: ' . $e->getMessage());
        return [];
    }
}

/**
 * Nosūta e-pastu (vienkārša versija)
 */
function sendEmail($to, $subject, $message) {
    // Šeit varētu būt integrācija ar SendGrid, Mailgun utt.
    // Pagaidām tikai log
    error_log("Email nosūtīts: $to - $subject");
    return true;
}

/**
 * Dzēš vecos failus no uploads direktorijas
 */
function cleanupOldFiles($directory, $maxAge = 30) {
    if (!is_dir($directory)) {
        return;
    }
    
    $files = glob($directory . '/*');
    $cutoff = time() - ($maxAge * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoff) {
            unlink($file);
        }
    }
}

/**
 * Logē lietotāja darbību
 */
function logActivity($type, $message, $userId = null, $adminId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO activity_log (type, message, user_id, admin_id, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$type, $message, $userId, $adminId]);
    } catch (PDOException $e) {
        error_log('logActivity kļūda: ' . $e->getMessage());
    }
}

/**
 * Pārbauda CSRF token (ja nepieciešams)
 */
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        throw new Exception('Nederīgs CSRF token');
    }
    return true;
}

/**
 * Ģenerē CSRF token
 */
function generateCSRF() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Sanitize input dati
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Pārbauda faila tipu
 */
function validateImageUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Atļauti tikai JPEG, PNG un GIF faili');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Fails ir pārāk liels (maksimums 5MB)');
    }
    
    return true;
}

/**
 * Izveido drošu faila nosaukumu
 */
function generateSafeFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    
    return $timestamp . '_' . $random . '.' . strtolower($extension);
}

/**
 * Pārbauda vai lietotājs ir bloķēts
 */
function isUserBlocked($pdo, $userId) {
    try {
        $stmt = $pdo->prepare('SELECT is_blocked FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['is_blocked'] : false;
    } catch (PDOException $e) {
        error_log('isUserBlocked kļūda: ' . $e->getMessage());
        return false;
    }
}

/**
 * Aprēķina attālumu starp diviem punktiem (ja nepieciešams geolokatācija)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

/**
 * Rate limiting funkcija
 */
function checkRateLimit($identifier, $maxRequests = 10, $timeWindow = 60) {
    $cacheKey = "rate_limit_" . md5($identifier);
    
    // Vienkārša implementācija ar failiem (produkcijā izmantot Redis vai Memcached)
    $file = sys_get_temp_dir() . '/' . $cacheKey;
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $currentTime = time();
        
        // Notīra vecos ierakstus
        $data = array_filter($data, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        if (count($data) >= $maxRequests) {
            throw new Exception('Pārāk daudz pieprasījumu. Mēģiniet vēlāk.');
        }
        
        $data[] = $currentTime;
    } else {
        $data = [time()];
    }
    
    file_put_contents($file, json_encode($data));
    return true;
}

/**
 * Debugging funkcija - izdrukā mainīgo skaisti
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}
?>