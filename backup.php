<?php
require_once 'config.php';
require_once 'functions.php';

checkAuth();

$action = $_GET['action'] ?? '';
$message = '';
$error = '';

// Создание бэкапа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $description = trim($_POST['description'] ?? '');
    
    if (empty($description)) {
        $description = 'Ручной бэкап от ' . date('Y-m-d H:i:s');
    }
    
    // Создаем бэкап
    $backupFile = BACKUP_PATH . '/backup_' . date('Y-m-d_H-i-s') . '.tar.gz';
    
    $command = sprintf(
        'sudo tar -czf %s --exclude="*.log" --exclude="*.tmp" %s %s 2>&1',
        escapeshellarg($backupFile),
        escapeshellarg(AMNEZIA_PATH),
        escapeshellarg(dirname(CLIENTS_DB))
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0) {
        // Сохраняем информацию о бэкапе
        $backupInfo = [
            'file' => $backupFile,
            'description' => $description,
            'created' => date('Y-m-d H:i:s'),
            'size' => filesize($backupFile),
            'type' => 'manual'
        ];
        
        // Сохраняем в файл бэкапов
        $backups = getBackupsList();
        $backups[] = $backupInfo;
        saveBackupsList($backups);
        
        $message = 'Бэкап успешно создан: ' . basename($backupFile);
        logAction('backup_create', 'Создан бэкап: ' . $description);
    } else {
        $error = 'Ошибка при создании бэкапа: ' . implode("\n", $output);
        logAction('backup_error', 'Ошибка создания бэкапа: ' . $error);
    }
}

// Восстановление из бэкапа
if ($action === 'restore' && isset($_GET['file'])) {
    $backupFile = BACKUP_PATH . '/' . basename($_GET['file']);
    
    if (file_exists($backupFile)) {
        $command = sprintf(
            'sudo tar -xzf %s -C / 2>&1',
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            // Перезапускаем Docker контейнеры
            $restartCommand = sprintf(
                'cd %s && sudo docker-compose restart 2>&1',
                escapeshellarg(AMNEZIA_PATH)
            );
            exec($restartCommand);
            
            $message = 'Система успешно восстановлена из бэкапа!';
            logAction('backup_restore', 'Восстановление из бэкапа: ' . basename($backupFile));
        } else {
            $error = 'Ошибка при восстановлении: ' . implode("\n", $output);
        }
    } else {
        $error = 'Файл бэкапа не найден';
    }
    
    header('Location: backup.php?message=' . urlencode($message) . '&error=' . urlencode($error));
    exit;
}

// Удаление бэкапа
if ($action === 'delete' && isset($_GET['file'])) {
    $backupFile = BACKUP_PATH . '/' . basename($_GET['file']);
    
    if (file_exists($backupFile)) {
        if (unlink($backupFile)) {
            // Удаляем из списка бэкапов
            $backups = getBackupsList();
            $backups = array_filter($backups, function($b) use ($backupFile) {
                return $b['file'] !== $backupFile;
            });
            saveBackupsList($backups);
            
            $message = 'Бэкап успешно удален';
            logAction('backup_delete', 'Удален бэкап: ' . basename($backupFile));
        } else {
            $error = 'Ошибка при удалении файла';
        }
    } else {
        $error = 'Файл бэкапа не найден';
    }
    
    header('Location: backup.php?message=' . urlencode($message) . '&error=' . urlencode($error));
    exit;
}

// Получение сообщений из URL
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Функции для работы с бэкапами
function getBackupsList() {
    $backupsFile = BACKUP_PATH . '/backups.json';
    
    if (file_exists($backupsFile)) {
        $data = file_get_contents($backupsFile);
        $backups = json_decode($data, true);
        return is_array($backups) ? $backups : [];
    }
    
    return [];
}

function saveBackupsList($backups) {
    $backupsFile = BACKUP_PATH . '/backups.json';
    file_put_contents($backupsFile, json_encode($backups, JSON_PRETTY_PRINT));
}

// Получаем список бэкапов
$backups = getBackupsList();
// Сортируем по дате (новые сверху)
usort($backups, function($a, $b) {
    return strtotime($b['created']) - strtotime($a['created']);
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бэкапы - AmneziaWG Панель</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-save"></i> Управление бэкапами</h1>
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
        
        <!-- Форма создания бэкапа -->
        <div class="card mb-20">
            <h2><i class="fas fa-plus-circle"></i> Создать новый бэкап</h2>
            <p class="mb-20">Создайте резервную копию всей системы AmneziaWG</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="description">Описание бэкапа</label>
                    <input type="text" 
                           id="description" 
                           name="description" 
                           placeholder="Например: Бэкап перед обновлением системы" 
                           maxlength="100">
                </div>
                
                <button type="submit" name="create_backup" class="btn btn-primary">
                    <i class="fas fa-save"></i> Создать бэкап
                </button>
            </form>
        </div>
        
        <!-- Список бэкапов -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Список бэкапов</h2>
            
            <?php if (empty($backups)): ?>
                <div class="no-data">
                    <i class="fas fa-archive fa-3x"></i>
                    <h3>Бэкапы не созданы</h3>
                    <p>Создайте первый бэкап для защиты вашей конфигурации</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Дата создания</th>
                                <th>Описание</th>
                                <th>Размер</th>
                                <th>Тип</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($backup['created'])); ?></td>
                                <td><?php echo htmlspecialchars($backup['description']); ?></td>
                                <td><?php echo formatBytes($backup['size']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $backup['type'] === 'manual' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $backup['type'] === 'manual' ? 'Ручной' : 'Авто'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?php echo $backup['file']; ?>" 
                                       class="btn-icon" 
                                       title="Скачать" 
                                       download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button onclick="restoreBackup('<?php echo basename($backup['file']); ?>')" 
                                            class="btn-icon btn-warning" 
                                            title="Восстановить">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    
                                    <button onclick="deleteBackup('<?php echo basename($backup['file']); ?>')" 
                                            class="btn-icon btn-danger" 
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Статистика бэкапов -->
                <div class="backup-stats mt-20">
                    <?php
                    $totalSize = array_sum(array_column($backups, 'size'));
                    $manualCount = count(array_filter($backups, function($b) {
                        return $b['type'] === 'manual';
                    }));
                    $autoCount = count($backups) - $manualCount;
                    ?>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Всего бэкапов</h3>
                                <p class="stat-number"><?php echo count($backups); ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-hand-paper"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Ручные</h3>
                                <p class="stat-number"><?php echo $manualCount; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Автоматические</h3>
                                <p class="stat-number"><?php echo $autoCount; ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <div class="stat-icon">
                                <i class="fas fa-weight-hanging"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Общий размер</h3>
                                <p class="stat-number"><?php echo formatBytes($totalSize); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Информация о бэкапах -->
        <div class="card mt-20">
            <h2><i class="fas fa-info-circle"></i> Информация о бэкапах</h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Что входит в бэкап</h3>
                    <ul>
                        <li>Конфигурация AmneziaWG сервера</li>
                        <li>Ключи WireGuard</li>
                        <li>Конфигурации всех клиентов</li>
                        <li>Настройки Docker контейнеров</li>
                    </ul>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Рекомендации</h3>
                    <ul>
                        <li>Создавайте бэкап перед любыми изменениями</li>
                        <li>Храните бэкапы в безопасном месте</li>
                        <li>Проверяйте целостность бэкапов</li>
                        <li>Не удаляйте все бэкапы одновременно</li>
                    </ul>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-cogs"></i>
                    <h3>Автоматизация</h3>
                    <p>Автоматические бэкапы создаются каждый день в 3:00</p>
                    <p>Старые бэкапы автоматически удаляются (хранится 10 последних)</p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Восстановление из бэкапа
        function restoreBackup(filename) {
            if (confirm('Внимание! Восстановление из бэкапа перезапишет текущую конфигурацию.\nВы уверены, что хотите продолжить?')) {
                window.location.href = `backup.php?action=restore&file=${filename}`;
            }
        }
        
        // Удаление бэкапа
        function deleteBackup(filename) {
            if (confirm('Вы уверены, что хотите удалить этот бэкап?')) {
                window.location.href = `backup.php?action=delete&file=${filename}`;
            }
        }
        
        // Форматирование байтов в читаемый вид
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
    
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-item i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .info-item h3 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .info-item ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-item li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item li:last-child {
            border-bottom: none;
        }
        
        .backup-stats {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--gray-color);
        }
        
        .fa-spin {
            animation: fa-spin 2s infinite linear;
        }
        
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
