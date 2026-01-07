<?php
require_once 'config.php';

/**
 * Класс для управления AmneziaWG
 */
class AmneziaManager {
    
    /**
     * Получить список всех клиентов
     */
    public static function getClients() {
        if (!file_exists(CLIENTS_DB)) {
            return [];
        }
        
        $data = file_get_contents(CLIENTS_DB);
        $clients = json_decode($data, true);
        
        return is_array($clients) ? $clients : [];
    }
    
    /**
     * Сохранить список клиентов
     */
    public static function saveClients($clients) {
        file_put_contents(CLIENTS_DB, json_encode($clients, JSON_PRETTY_PRINT));
        logAction('clients_save', 'Клиенты сохранены: ' . count($clients));
    }
    
    /**
     * Создать нового клиента
     */
    public static function createClient($name, $email = '', $notes = '') {
        $clients = self::getClients();
        
        // Генерация ID клиента
        $clientId = 'client_' . time() . '_' . bin2hex(random_bytes(4));
        
        // Пути к файлам
        $configFile = CLIENTS_PATH . '/' . $clientId . '.conf';
        $qrFile = CLIENTS_PATH . '/' . $clientId . '.png';
        
        // Создание директории клиентов, если не существует
        if (!is_dir(CLIENTS_PATH)) {
            mkdir(CLIENTS_PATH, 0755, true);
        }
        
        try {
            // Получаем конфигурацию сервера
            $serverConfig = self::getServerConfig();
            if (!$serverConfig) {
                throw new Exception('Конфигурация сервера не найдена');
            }
            
            // Генерируем ключи клиента
            $privateKey = self::executeCommand("wg genkey");
            $publicKey = self::executeCommand("echo '$privateKey' | wg pubkey");
            
            // Создаем IP адрес для клиента
            $clientIP = self::generateClientIP(count($clients) + 1);
            
            // Создаем конфигурационный файл клиента
            $configContent = self::generateClientConfig($clientId, $privateKey, $clientIP, $serverConfig);
            
            // Сохраняем конфигурационный файл
            file_put_contents($configFile, $configContent);
            
            // Генерируем QR код
            self::generateQRCode($configContent, $qrFile);
            
            // Добавляем клиента в базу данных
            $client = [
                'id' => $clientId,
                'name' => $name,
                'email' => $email,
                'notes' => $notes,
                'created' => date('Y-m-d H:i:s'),
                'updated' => date('Y-m-d H:i:s'),
                'config_file' => $configFile,
                'qr_file' => $qrFile,
                'active' => true,
                'private_key' => $privateKey,
                'public_key' => $publicKey,
                'ip_address' => $clientIP,
                'last_connected' => null
            ];
            
            $clients[$clientId] = $client;
            self::saveClients($clients);
            
            // Добавляем клиента в конфигурацию сервера
            self::addClientToServerConfig($clientId, $publicKey, $clientIP);
            
            logAction('client_create', 'Создан клиент: ' . $name . ' (ID: ' . $clientId . ')');
            
            return $client;
            
        } catch (Exception $e) {
            logAction('client_create_error', 'Ошибка создания клиента: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удалить клиента
     */
    public static function deleteClient($clientId) {
        $clients = self::getClients();
        
        if (!isset($clients[$clientId])) {
            return false;
        }
        
        $client = $clients[$clientId];
        
        // Удаляем файлы клиента
        if (file_exists($client['config_file'])) {
            unlink($client['config_file']);
        }
        
        if (file_exists($client['qr_file'])) {
            unlink($client['qr_file']);
        }
        
        // Удаляем клиента из конфигурации сервера
        self::removeClientFromServerConfig($clientId);
        
        // Удаляем из базы данных
        unset($clients[$clientId]);
        self::saveClients($clients);
        
        logAction('client_delete', 'Удален клиент: ' . $client['name'] . ' (ID: ' . $clientId . ')');
        
        return true;
    }
    
    /**
     * Получить конфигурацию сервера
     */
    private static function getServerConfig() {
        if (!file_exists(SERVER_CONFIG_PATH)) {
            return false;
        }
        
        $config = json_decode(file_get_contents(SERVER_CONFIG_PATH), true);
        return $config ?: [];
    }
    
    /**
     * Выполнить команду в системе
     */
    private static function executeCommand($command) {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Ошибка выполнения команды: ' . implode("\n", $output));
        }
        
        return trim(implode("\n", $output));
    }
    
    /**
     * Сгенерировать IP адрес для клиента
     */
    private static function generateClientIP($clientNumber) {
        // Базовый IP из конфигурации сервера
        $serverConfig = self::getServerConfig();
        $serverIP = $serverConfig['server_ip'] ?? '10.0.0.1';
        
        // Разбираем IP сервера
        $parts = explode('.', $serverIP);
        $subnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
        
        // Генерируем IP клиента (начиная с .2)
        $clientIP = $subnet . '.' . ($clientNumber + 1);
        
        return $clientIP;
    }
    
    /**
     * Сгенерировать конфигурационный файл клиента
     */
    private static function generateClientConfig($clientId, $privateKey, $clientIP, $serverConfig) {
        $serverIP = $serverConfig['server_ip'] ?? $_SERVER['SERVER_ADDR'];
        $serverPort = $serverConfig['server_port'] ?? WG_DEFAULT_PORT;
        $serverPublicKey = $serverConfig['server_public_key'] ?? '';
        
        $config = "[Interface]\n";
        $config .= "PrivateKey = $privateKey\n";
        $config .= "Address = $clientIP/24\n";
        $config .= "DNS = " . WG_DNS . "\n\n";
        $config .= "[Peer]\n";
        $config .= "PublicKey = $serverPublicKey\n";
        $config .= "Endpoint = $serverIP:$serverPort\n";
        $config .= "AllowedIPs = 0.0.0.0/0\n";
        $config .= "PersistentKeepalive = 25\n";
        
        return $config;
    }
    
    /**
     * Сгенерировать QR код
     */
    private static function generateQRCode($configContent, $qrFile) {
        // Если библиотека php-qrcode установлена
        if (class_exists('QRcode')) {
            QRcode::png($configContent, $qrFile, 'L', 10, 2);
        } else {
            // Альтернативный метод - создаем текстовый файл с инструкцией
            $qrInfo = "Для генерации QR кода установите php-qrcode\n";
            $qrInfo .= "Или используйте команду: qrencode -t png -o '$qrFile' '" . addslashes($configContent) . "'";
            file_put_contents($qrFile . '.txt', $qrInfo);
        }
    }
    
    /**
     * Получить количество активных клиентов
     */
    public static function getActiveClientsCount() {
        $clients = self::getClients();
        $activeCount = 0;
        
        foreach ($clients as $client) {
            if ($client['active']) {
                $activeCount++;
            }
        }
        
        return $activeCount;
    }
    
    /**
     * Получить статус сервера
     */
    public static function getServerStatus() {
        $command = "sudo docker-compose -f " . AMNEZIA_PATH . "/docker-compose.yml ps amneziawg 2>&1";
        $output = shell_exec($command);
        
        if (strpos($output, 'Up') !== false) {
            return 'running';
        }
        
        return 'stopped';
    }
    
    /**
     * Получить количество бэкапов
     */
    public static function getBackupsCount() {
        if (!is_dir(BACKUP_PATH)) {
            return 0;
        }
        
        $files = glob(BACKUP_PATH . '/*.tar.gz');
        return count($files);
    }
    
    /**
     * Получить последние действия
     */
    public static function getRecentActivities($limit = 5) {
        $logFile = LOG_PATH . '/actions-' . date('Y-m-d') . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, $limit);
        
        $activities = [];
        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\] (.*?): (.*?) - (.*)/', $line, $matches)) {
                $activities[] = [
                    'time' => $matches[1],
                    'user' => $matches[2],
                    'action' => $matches[3],
                    'message' => $matches[4],
                    'icon' => self::getActionIcon($matches[3])
                ];
            }
        }
        
        return $activities;
    }
    
    /**
     * Получить иконку для действия
     */
    private static function getActionIcon($action) {
        $icons = [
            'client_create' => 'fa-user-plus',
            'client_delete' => 'fa-user-minus',
            'backup_create' => 'fa-save',
            'server_restart' => 'fa-redo',
            'login' => 'fa-sign-in-alt',
            'logout' => 'fa-sign-out-alt'
        ];
        
        return $icons[$action] ?? 'fa-circle';
    }
}

/**
 * Вспомогательные функции для вывода
 */
function displayAlert($type, $message) {
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-error',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $classes[$type] ?? 'alert-info';
    
    echo '<div class="alert ' . $class . '">';
    echo '<i class="fas fa-info-circle"></i> ';
    echo htmlspecialchars($message);
    echo '</div>';
}

/**
 * Проверка существования AmneziaWG
 */
function checkAmneziaInstallation() {
    if (!file_exists(AMNEZIA_PATH)) {
        return [
            'installed' => false,
            'message' => 'AmneziaWG не установлен в директории: ' . AMNEZIA_PATH
        ];
    }
    
    if (!file_exists(AMNEZIA_PATH . '/docker-compose.yml')) {
        return [
            'installed' => false,
            'message' => 'Файл docker-compose.yml не найден'
        ];
    }
    
    return ['installed' => true, 'message' => 'AmneziaWG установлен'];
}
?>
