<?php
session_start();

// ============================================
// НАСТРОЙКИ БЕЗОПАСНОСТИ - ИЗМЕНИТЕ ЭТО!
// ============================================

// Имя пользователя для входа в панель
define('ADMIN_USERNAME', 'admin');

// Пароль для входа (сгенерируйте новый!)
// Используйте: https://www.php.net/manual/ru/function.password-hash.php
// Пример: echo password_hash('ваш_пароль', PASSWORD_DEFAULT);
define('ADMIN_PASSWORD_HASH', '$2y$10$YourGeneratedHashHere');

// Время жизни сессии в секундах (30 минут)
define('SESSION_TIMEOUT', 1800);

// ============================================
// ПУТИ К СИСТЕМНЫМ ФАЙЛАМ
// ============================================

// Путь к AmneziaWG (обычно /opt/amneziawg)
define('AMNEZIA_PATH', '/opt/amneziawg');

// Путь к конфигурации сервера
define('SERVER_CONFIG_PATH', AMNEZIA_PATH . '/server_config.json');

// Путь к директории клиентов
define('CLIENTS_PATH', AMNEZIA_PATH . '/clients');

// ============================================
// НАСТРОЙКИ ПАНЕЛИ
// ============================================

// База данных клиентов (JSON файл)
define('CLIENTS_DB', __DIR__ . '/data/clients.json');

// Директория для бэкапов
define('BACKUP_PATH', __DIR__ . '/backups');

// Максимальное количество хранимых бэкапов
define('MAX_BACKUPS', 10);

// Путь для логов
define('LOG_PATH', __DIR__ . '/logs');

// ============================================
// НАСТРОЙКИ VPN СЕРВЕРА
// ============================================

// Порт WireGuard по умолчанию
define('WG_DEFAULT_PORT', 51820);

// DNS сервер для клиентов
define('WG_DNS', '1.1.1.1');

// ============================================
// ФУНКЦИИ БЕЗОПАСНОСТИ
// ============================================

// Включить режим отладки (только для разработки!)
define('DEBUG_MODE', false);

// IP адреса, которым разрешен доступ (пустой массив = всем)
$ALLOWED_IPS = [];

// ============================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ============================================

/**
 * Проверка авторизации пользователя
 */
function checkAuth() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
    
    // Проверка таймаута сессии
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    // Обновление времени последней активности
    $_SESSION['last_activity'] = time();
}

/**
 * Проверка доступа по IP
 */
function checkIPAccess() {
    global $ALLOWED_IPS;
    
    if (empty($ALLOWED_IPS)) {
        return true; // Все IP разрешены
    }
    
    $userIP = $_SERVER['REMOTE_ADDR'];
    return in_array($userIP, $ALLOWED_IPS);
}

/**
 * Логирование действий
 */
function logAction($action, $details = '') {
    $logFile = LOG_PATH . '/actions-' . date('Y-m-d') . '.log';
    $logEntry = sprintf(
        "[%s] %s: %s - %s\n",
        date('Y-m-d H:i:s'),
        $_SESSION['username'] ?? 'guest',
        $action,
        $details
    );
    
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Инициализация ошибок
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Проверка обязательных директорий
$requiredDirs = [dirname(CLIENTS_DB), BACKUP_PATH, LOG_PATH];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Создание файла клиентов, если не существует
if (!file_exists(CLIENTS_DB)) {
    file_put_contents(CLIENTS_DB, json_encode([], JSON_PRETTY_PRINT));
}
?>
