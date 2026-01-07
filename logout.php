<?php
session_start();

// Логирование выхода
if (isset($_SESSION['username'])) {
    require_once 'config.php';
    logAction('logout', 'Пользователь вышел из системы');
}

// Уничтожение сессии
session_unset();
session_destroy();

// Перенаправление на страницу входа
header('Location: login.php');
exit;
?>
