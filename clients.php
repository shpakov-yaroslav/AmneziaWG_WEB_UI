<?php
require_once 'config.php';
require_once 'functions.php';

checkAuth();

$action = $_GET['action'] ?? 'list';
$clientId = $_GET['id'] ?? null;
$message = '';
$error = '';

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name)) {
            $error = 'Имя клиента обязательно для заполнения';
        } else {
            $client = AmneziaManager::createClient($name, $email, $notes);
            if ($client) {
                $_SESSION['success_message'] = 'Клиент успешно создан!';
                header('Location: clients.php');
                exit;
            } else {
                $error = 'Ошибка при создании клиента';
            }
        }
    } elseif ($action === 'edit' && $clientId) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        $clients = AmneziaManager::getClients();
        if (isset($clients[$clientId])) {
            $clients[$clientId]['name'] = $name;
            $clients[$clientId]['email'] = $email;
            $clients[$clientId]['notes'] = $notes;
            $clients[$clientId]['updated'] = date('Y-m-d H:i:s');
            
            AmneziaManager::saveClients($clients);
            $_SESSION['success_message'] = 'Клиент успешно обновлен!';
            header('Location: clients.php');
            exit;
        }
    }
}

// Обработка GET действий
if ($action === 'delete' && $clientId) {
    if (AmneziaManager::deleteClient($clientId)) {
        $_SESSION['success_message'] = 'Клиент успешно удален!';
        header('Location: clients.php');
        exit;
    } else {
        $error = 'Ошибка при удалении клиента';
    }
} elseif ($action === 'toggle' && $clientId) {
    $clients = AmneziaManager::getClients();
    if (isset($clients[$clientId])) {
        $clients[$clientId]['active'] = !$clients[$clientId]['active'];
        $clients[$clientId]['updated'] = date('Y-m-d H:i:s');
        
        AmneziaManager::saveClients($clients);
        header('Location: clients.php');
        exit;
    }
}

// Получение сообщений из сессии
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Клиенты - AmneziaWG Панель</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Управление клиентами</h1>
            <a href="clients.php?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Новый клиент
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'create' || $action === 'edit'): ?>
            <!-- Форма создания/редактирования клиента -->
            <div class="form-container">
                <h2><?php echo $action === 'create' ? 'Создание нового клиента' : 'Редактирование клиента'; ?></h2>
                
                <?php
                $currentClient = null;
                if ($action === 'edit' && $clientId) {
                    $clients = AmneziaManager::getClients();
                    $currentClient = $clients[$clientId] ?? null;
                    if (!$currentClient) {
                        echo '<div class="alert alert-error">Клиент не найден</div>';
                        include 'includes/footer.php';
                        exit;
                    }
                }
                ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Имя клиента *</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($currentClient['name'] ?? ''); ?>" 
                               placeholder="Например: Иван Иванов" 
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email адрес</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($currentClient['email'] ?? ''); ?>" 
                               placeholder="client@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Примечания</label>
                        <textarea id="notes" 
                                  name="notes" 
                                  rows="3" 
                                  placeholder="Дополнительная информация о клиенте"><?php echo htmlspecialchars($currentClient['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $action === 'create' ? 'Создать клиента' : 'Сохранить изменения'; ?>
                        </button>
                        <a href="clients.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Список клиентов -->
            <div class="table-container">
                <?php
                $clients = AmneziaManager::getClients();
                
                if (empty($clients)) {
                    echo '<div class="no-data">';
                    echo '<i class="fas fa-users fa-3x"></i>';
                    echo '<h3>Нет созданных клиентов</h3>';
                    echo '<p>Создайте первого клиента, чтобы начать работу</p>';
                    echo '<a href="clients.php?action=create" class="btn btn-primary mt-20">';
                    echo '<i class="fas fa-plus"></i> Создать первого клиента';
                    echo '</a>';
                    echo '</div>';
                } else {
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>IP Адрес</th>
                            <th>Статус</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><code><?php echo substr($client['id'], 0, 8) . '...'; ?></code></td>
                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                            <td><?php echo htmlspecialchars($client['email'] ?: '—'); ?></td>
                            <td><code><?php echo htmlspecialchars($client['ip_address'] ?? '—'); ?></code></td>
                            <td>
                                <span class="status-badge <?php echo $client['active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $client['active'] ? 'Активен' : 'Неактивен'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($client['created'])); ?></td>
                            <td class="actions">
                                <button onclick="showQR('<?php echo $client['id']; ?>')" 
                                        class="btn-icon" 
                                        title="QR код">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                
                                <a href="clients.php?action=download&id=<?php echo $client['id']; ?>" 
                                   class="btn-icon" 
                                   title="Скачать конфиг">
                                    <i class="fas fa-download"></i>
                                </a>
                                
                                <a href="clients.php?action=edit&id=<?php echo $client['id']; ?>" 
                                   class="btn-icon" 
                                   title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <a href="clients.php?action=toggle&id=<?php echo $client['id']; ?>" 
                                   class="btn-icon <?php echo $client['active'] ? 'btn-warning' : ''; ?>" 
                                   title="<?php echo $client['active'] ? 'Отключить' : 'Включить'; ?>">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                
                                <button onclick="confirmDelete('<?php echo $client['id']; ?>')" 
                                        class="btn-icon btn-danger" 
                                        title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Модальное окно QR кода -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeQRModal()">&times;</span>
            <h2><i class="fas fa-qrcode"></i> QR код подключения</h2>
            <div id="qrContainer" class="qr-container">
                <!-- QR код будет загружен здесь -->
            </div>
            <div class="text-center mt-20">
                <p>Отсканируйте этот QR код в приложении WireGuard для подключения</p>
                <button onclick="downloadConfig()" class="btn btn-primary">
                    <i class="fas fa-download"></i> Скачать конфигурацию
                </button>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Показать QR код
        function showQR(clientId) {
            const modal = document.getElementById('qrModal');
            const container = document.getElementById('qrContainer');
            
            // Показать загрузку
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Загрузка QR кода...</div>';
            modal.style.display = 'flex';
            
            // Загрузить QR код через API
            fetch(`api.php?action=get_qr&client_id=${clientId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.qr_image) {
                        container.innerHTML = `<img src="${data.qr_image}" alt="QR Code">`;
                    } else {
                        container.innerHTML = '<div class="alert alert-error">Не удалось загрузить QR код</div>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-error">Ошибка загрузки: ' + error + '</div>';
                });
        }
        
        // Закрыть модальное окно
        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }
        
        // Закрытие по клику вне окна
        window.onclick = function(event) {
            const modal = document.getElementById('qrModal');
            if (event.target === modal) {
                closeQRModal();
            }
        }
        
        // Подтверждение удаления
        function confirmDelete(clientId) {
            if (confirm('Вы уверены, что хотите удалить этого клиента?\nЭто действие нельзя отменить.')) {
                window.location.href = `clients.php?action=delete&id=${clientId}`;
            }
        }
        
        // Скачать конфигурацию
        function downloadConfig() {
            // Получаем clientId из URL QR кода или другим способом
            const urlParams = new URLSearchParams(window.location.search);
            const clientId = urlParams.get('client_id');
            
            if (clientId) {
                window.location.href = `api.php?action=download_config&client_id=${clientId}`;
            }
        }
    </script>
</body>
</html>
