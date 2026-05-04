<?php
// Shared application configuration and helpers.

function loadEnvFile($path) {
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

loadEnvFile(__DIR__ . '/../.env');

if (envValue('APP_ENV', 'production') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
}

function envValue($keys, $default = null) {
    foreach ((array) $keys as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
}

define('DB_HOST', envValue(['DB_HOST', 'MYSQLHOST'], 'localhost'));
define('DB_USER', envValue(['DB_USER', 'MYSQLUSER'], 'root'));
define('DB_PASS', envValue(['DB_PASS', 'MYSQLPASSWORD'], ''));
define('DB_NAME', envValue(['DB_NAME', 'MYSQLDATABASE'], 'school_system'));
define('DB_PORT', (int) envValue(['DB_PORT', 'MYSQLPORT'], 3306));
$allowSetup = getenv('ALLOW_SETUP_SCRIPTS') === 'true';

function getConnection() {
    if (!class_exists('mysqli')) {
        error_log('Database connection error: PHP mysqli extension is not installed or enabled.');
        internalServerError();
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (Throwable $e) {
        error_log('Database connection error: ' . $e->getMessage());
        internalServerError();
    }
}

function isApiRequest() {
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
}

function internalServerError() {
    http_response_code(500);

    if (isApiRequest()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'data' => null
        ]);
        exit;
    }

    die('Internal server error');
}

function configureCors($methods = 'GET, POST, OPTIONS') {
    header('Content-Type: application/json');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = array_filter(array_map('trim', explode(',', envValue('FRONTEND_ORIGIN', ''))));

    if ($origin && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    } elseif (!$origin || empty($allowedOrigins)) {
        header('Access-Control-Allow-Origin: *');
    }

    header('Access-Control-Allow-Methods: ' . $methods);
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function startSession() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $savePath = envValue('SESSION_SAVE_PATH', __DIR__ . '/../storage/sessions');
    if ($savePath !== '' && !is_dir($savePath)) {
        @mkdir($savePath, 0775, true);
    }
    if ($savePath !== '' && is_writable($savePath)) {
        session_save_path($savePath);
    } elseif ($savePath !== '') {
        error_log('Session save path is not writable: ' . $savePath);
        internalServerError();
    }

    $secure = envValue('SESSION_SECURE', '') === 'true'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => envValue('SESSION_SAMESITE', $secure ? 'None' : 'Lax'),
    ];

    $sessionDomain = envValue('SESSION_DOMAIN', '');
    if ($sessionDomain !== '') {
        $cookieParams['domain'] = $sessionDomain;
    }

    session_set_cookie_params($cookieParams);

    session_start();
}

function jsonResponse($success, $message, $data = null) {
    if (http_response_code() === 200 && $success === false) {
        http_response_code(400);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function apiResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    jsonResponse($success, $message, $data);
}

function requireMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        apiResponse(false, 'Method not allowed', null, 405);
    }
}

function requireStudentSession() {
    startSession();

    if (!isset($_SESSION['student_logged_in'], $_SESSION['student_id']) || $_SESSION['student_logged_in'] !== true) {
        apiResponse(false, 'Unauthorized', null, 401);
    }

    return $_SESSION['student_id'];
}

function requireAdminSession() {
    startSession();

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        apiResponse(false, 'Unauthorized', null, 401);
    }
}

function generateStudentId($conn) {
    $year = date('Y');
    $prefix = 'IPMC' . $year;

    $stmt = $conn->prepare(
        'SELECT MAX(CAST(SUBSTRING(student_id, 9) AS UNSIGNED)) AS max_id
         FROM students
         WHERE student_id LIKE ?'
    );
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nextId = ($row['max_id'] ?? 0) + 1;
    return $prefix . str_pad($nextId, 3, '0', STR_PAD_LEFT);
}

function sanitizeInput($data) {
    $data = trim((string) $data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function getGradePoints($grade) {
    $gradePoints = [
        'A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0,
        'B-' => 2.7, 'C+' => 2.3, 'C' => 2.0, 'D' => 1.0, 'F' => 0.0
    ];
    return $gradePoints[$grade] ?? 0.0;
}

function calculateGPA($results) {
    $totalPoints = 0;
    $totalCredits = 0;

    foreach ($results as $result) {
        $points = getGradePoints($result['grade']);
        $credits = (int) $result['credits'];
        $totalPoints += $points * $credits;
        $totalCredits += $credits;
    }

    return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;
}

function isAdminLoggedIn() {
    startSession();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function requireSetupScriptsEnabled() {
    global $allowSetup;

    if ($allowSetup !== true) {
        http_response_code(403);
        die('Forbidden');
    }
}
?>
