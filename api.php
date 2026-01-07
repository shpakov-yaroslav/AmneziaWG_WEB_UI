<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Проверка авторизации для API
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if (empty($apiKey) || $apiKey !== ADMIN_PASSWORD_HASH) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Неавторизованный доступ']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_qr':
        $clientId = $_GET['client_id'] ?? '';
        if (empty($clientId)) {
            echo json_encode(['success' => false, 'error' => 'Не указан ID клиента']);
            break;
        }
        
        $clients = AmneziaManager::getClients();
        if (!isset($clients[$clientId]) || !file_exists($clients[$clientId]['qr_file'])) {
            echo json_encode(['success' => false, 'error' => 'QR код не найден']);
            break;
        }
        
        $qrImage = base64_encode(file_get_contents($clients[$clientId]['qr_file']));
        echo json_encode([
            'success' => true,
            'qr_image' => 'data:image/png;base64,' . $qrImage,
            'client_name' => $clients[$clientId]['name']
        ]);
        break;
        
    case 'download_config':
        $clientId = $_GET['client_id'] ?? '';
        if (empty($clientId)) {
            http_response_code(400);
            echo 'Не указан ID клиента';
            exit;
        }
        
        $clients = AmneziaManager::getClients();
        if (!isset($clients[$clientId]) || !file_exists($clients[$clientId]['config_file'])) {
            http_response_code(404);
            echo 'Конфигурационный файл не найден';
            exit;
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $clientId . '.conf"');
        readfile($clients[$clientId]['config_file']);
        break;
        
    case 'get_stats':
        echo json_encode([
            'success' => true,
            'clients_count' => AmneziaManager::getActiveClientsCount(),
            'server_status' => AmneziaManager::getServerStatus(),
            'backups_count' => AmneziaManager::getBackupsCount(),
            'timestamp' => time()
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
}
?>
