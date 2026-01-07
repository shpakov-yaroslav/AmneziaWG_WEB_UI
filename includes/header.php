<?php
// Проверяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header>
    <nav>
        <a href="index.php" class="logo">
            <i class="fas fa-shield-alt"></i>
            <span>AmneziaWG</span>
        </a>
        
        <?php if ($isLoggedIn): ?>
        <ul class="nav-links">
            <li>
                <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
            </li>
            <li>
                <a href="clients.php" class="<?php echo $currentPage === 'clients.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Клиенты</span>
                </a>
            </li>
            <li>
                <a href="backup.php" class="<?php echo $currentPage === 'backup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-save"></i>
                    <span>Бэкапы</span>
                </a>
            </li>
            <li>
                <a href="logs.php" class="<?php echo $currentPage === 'logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Логи</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Настройки</span>
                </a>
            </li>
        </ul>
        
        <div class="user-menu">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Пользователь'); ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-sign-out-alt"></i>
                Выйти
            </a>
        </div>
        <?php endif; ?>
    </nav>
</header>
