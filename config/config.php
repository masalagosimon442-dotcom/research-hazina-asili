<?php
/**
 * Application Configuration — v3.0
 * Loads from .env file when available, falls back to defaults.
 */

// Load .env — use phpdotenv if available, otherwise parse manually
$envFile = __DIR__ . '/../.env';
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv') && file_exists($envFile)) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
} elseif (file_exists($envFile)) {
    // Manual .env parser (fallback when composer dependencies not installed)
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Helper to read from $_ENV/.env with fallback
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

// ── Application ───────────────────────────────────────────────────────────────
define('APP_NAME',      env('APP_NAME', 'HAZINA ASILI'));
define('APP_FULL_NAME', env('APP_FULL_NAME', 'Mfumo wa Hifadhidata ya Misombo ya Asili ya Kikaboni'));
define('APP_VERSION',   env('APP_VERSION', '4.0.0'));
define('BASE_URL',      env('APP_URL', 'http://localhost/DB/project/'));
define('APP_ENV',       env('APP_ENV', 'development'));
define('APP_DEBUG',     filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));

// ── Timezone ──────────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Dar_es_Salaam');

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 3600));
define('RECORDS_PER_PAGE', (int)env('RECORDS_PER_PAGE', 15));

// ── File Uploads ──────────────────────────────────────────────────────────────
define('UPLOAD_DIR',    __DIR__ . '/../' . env('UPLOAD_DIR', 'assets/uploads/'));
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 5 * 1024 * 1024));

// ── Roles & Statuses ──────────────────────────────────────────────────────────
define('ROLE_ADMIN',      'admin');
define('ROLE_RESEARCHER', 'researcher');
define('STATUS_PENDING',  'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');

// ── Security ──────────────────────────────────────────────────────────────────
define('LOGIN_MAX_ATTEMPTS',    (int)env('LOGIN_MAX_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_MINUTES', (int)env('LOGIN_LOCKOUT_MINUTES', 15));
define('FORCE_HTTPS',           filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN));
define('ADMIN_PASSWORD',        env('ADMIN_PASSWORD', 'Admin@1234'));
define('ADMIN_USERNAME',        env('ADMIN_USERNAME', 'admin'));

// ── Email ─────────────────────────────────────────────────────────────────────
define('MAIL_ENABLED',      filter_var(env('MAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN));
define('MAIL_HOST',         env('MAIL_HOST', 'smtp.example.com'));
define('MAIL_PORT',         (int)env('MAIL_PORT', 587));
define('MAIL_USERNAME',     env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD',     env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION',   env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@hazina-asili.com'));
define('MAIL_FROM_NAME',    env('MAIL_FROM_NAME', 'HAZINA ASILI'));

// ── AI Assistant ──────────────────────────────────────────────────────────────
define('AI_ENABLED',  filter_var(env('AI_ENABLED', false), FILTER_VALIDATE_BOOLEAN));
define('AI_API_KEY',  env('AI_API_KEY', ''));
define('AI_API_URL',  env('AI_API_URL', 'https://api.openai.com/v1/chat/completions'));
define('AI_MODEL',    env('AI_MODEL', 'gpt-3.5-turbo'));

// ── OAuth ─────────────────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID',       env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET',   env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI',    env('GOOGLE_REDIRECT_URI', BASE_URL . 'controllers/oauth.php?provider=google&action=callback'));
define('MICROSOFT_CLIENT_ID',    env('MICROSOFT_CLIENT_ID', ''));
define('MICROSOFT_CLIENT_SECRET',env('MICROSOFT_CLIENT_SECRET', ''));
define('MICROSOFT_REDIRECT_URI', env('MICROSOFT_REDIRECT_URI', BASE_URL . 'controllers/oauth.php?provider=microsoft&action=callback'));

// ── Force HTTPS in production ─────────────────────────────────────────────────
if (FORCE_HTTPS && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: {$redirectUrl}", true, 301);
    exit;
}

// ── Start Session ─────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Session timeout (auto-logout on inactivity) ───────────────────────────────
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// ── Track Page Visit ──────────────────────────────────────────────────────────
// Only track on GET requests (not POST/AJAX)
// Track AFTER database is loaded
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['ajax'])) {
    try {
        // Database must be loaded first
        if (class_exists('Database')) {
            require_once __DIR__ . '/../models/SiteVisit.php';
            (new SiteVisit())->record();
        }
    } catch (Throwable $e) { 
        // Log error in debug mode
        if (APP_DEBUG) {
            error_log("SiteVisit tracking failed: " . $e->getMessage());
        }
    }
}

// ── Load Dependencies ─────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../helpers/RateLimiter.php';
require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../helpers/AIAssistant.php';
require_once __DIR__ . '/../helpers/ExternalSearch.php';

// ── Global Error/Exception Handlers ──────────────────────────────────────────
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) return false;
    $level = match (true) {
        in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]) => 'critical',
        in_array($errno, [E_WARNING, E_CORE_WARNING, E_USER_WARNING])            => 'warning',
        default => 'notice',
    };
    try {
        require_once __DIR__ . '/../models/ErrorLog.php';
        (new ErrorLog())->log($level, $errstr, $errfile, $errline);
    } catch (Throwable $e) {
        error_log("ErrorLog failed: " . $e->getMessage());
    }
    return false;
});

set_exception_handler(function (Throwable $e): void {
    try {
        require_once __DIR__ . '/../models/ErrorLog.php';
        (new ErrorLog())->log('critical', $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    } catch (Throwable $inner) {
        error_log("Exception handler failed: " . $inner->getMessage());
    }
    http_response_code(500);
    if (APP_DEBUG) {
        echo '<div style="font-family:sans-serif;padding:2rem;color:#721c24;background:#f8d7da;border-radius:8px;margin:2rem">
                <h2>⚠️ Application Error</h2>
                <p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>
                <pre style="font-size:0.8em;overflow:auto">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
              </div>';
    } else {
        echo '<div style="font-family:sans-serif;padding:2rem;color:#721c24;background:#f8d7da;border-radius:8px;margin:2rem">
                <h2>⚠️ Application Error</h2>
                <p>Something went wrong. The error has been logged. Please try again or contact the administrator.</p>
              </div>';
    }
    exit;
});
