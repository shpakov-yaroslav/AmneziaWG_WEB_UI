<?php
require_once 'config.php';
require_once 'functions.php';

// Проверка авторизации
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Обновление времени активности
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления AmneziaWG</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="welcome-card">
            <h1><i class="fas fa-shield-alt"></i> Добро пожаловать в панель управления AmneziaWG</h1>
            <p>Управляйте вашим VPN сервером через удобный веб-интерфейс</p>
        </div>
        
        <!-- Быстрые действия -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Быстрые действия</h2>
            <div class="action-grid">
                <a href="clients.php?action=create" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Добавить клиента</h3>
                    <p>Создать нового пользователя VPN</p>
                </a>
                
                <a href="clients.php" class="action-card">
                    <i class="fas fa-users"></i>
                    <h3>Все клиенты</h3>
                    <p>Управление существующими клиентами</p>
                </a>
                
                <a href="backup.php" class="action-card">
                    <i class="fas fa-save"></i>
                    <h3>Создать бэкап</h3>
                    <p>Резервное копирование системы</p>
                </a>
                
                <a href="logs.php" class="action-card">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Логи сервера</h3>
                    <p>Просмотр истории действий</p>
                </a>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="stats-section">
            <h2><i class="fas fa-chart-bar"></i> Статистика</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Активных клиентов</h3>
                        <p class="stat-number"><?php echo AmneziaManager::getActiveClientsCount(); ?></p>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Статус сервера</h3>
                        <p class="stat-number status-<?php echo AmneziaManager::getServerStatus(); ?>">
                            <?php echo AmneziaManager::getServerStatus() === 'running' ? 'Запущен' : 'Остановлен'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Доступно бэкапов</h3>
                        <p class="stat-number"><?php echo AmneziaManager::getBackupsCount(); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Последние действия -->
        <div class="recent-activity">
            <h2><i class="fas fa-history"></i> Последние действия</h2>
            <div class="activity-list">
                <?php
                $activities = AmneziaManager::getRecentActivities(5);
                if (empty($activities)) {
                    echo '<p class="no-data">Пока нет записей о действиях</p>';
                } else {
                    foreach ($activities as $activity) {
                        echo '<div class="activity-item">';
                        echo '<i class="fas ' . $activity['icon'] . '"></i>';
                        echo '<div class="activity-content">';
                        echo '<p>' . htmlspecialchars($activity['message']) . '</p>';
                        echo '<small>' . $activity['time'] . '</small>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/main.js"></script>
</body>
</html>
