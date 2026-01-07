<?php
session_start();
require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Проверка учетных данных
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Успешная авторизация
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
        
        // Логирование входа
        logAction('login', 'Успешный вход в систему');
        
        // Перенаправление на главную
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверное имя пользователя или пароль';
        logAction('login_failed', 'Неудачная попытка входа для пользователя: ' . $username);
    }
}

// Если был таймаут сессии
if (isset($_GET['timeout'])) {
    $error = 'Сессия истекла. Пожалуйста, войдите снова.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Панель управления AmneziaWG</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-with-icon input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .login-footer {
            margin-top: 20px;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .version {
            text-align: center;
            margin-top: 30px;
            color: #95a5a6;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h1>AmneziaWG Панель</h1>
                <p>Войдите для управления VPN сервером</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               placeholder="Введите имя пользователя" 
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Введите пароль" 
                               required>
                    </div>
                </div>
                
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Войти в панель
                </button>
            </form>
            
            <div class="login-footer">
                <p>По умолчанию: admin / admin123</p>
                <p><i class="fas fa-exclamation-triangle"></i> Не забудьте изменить пароль в config.php</p>
            </div>
            
            <div class="version">
                v1.0.0 • AmneziaWG Web Panel
            </div>
        </div>
    </div>
    
    <script>
        // Автофокус на поле пароля после ввода логина
        document.getElementById('username').addEventListener('input', function(e) {
            if (this.value.length >= 3) {
                document.getElementById('password').focus();
            }
        });
        
        // Показать/скрыть пароль
        const passwordInput = document.getElementById('password');
        const lockIcon = document.querySelector('.fa-lock');
        
        lockIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                lockIcon.classList.remove('fa-lock');
                lockIcon.classList.add('fa-unlock');
            } else {
                passwordInput.type = 'password';
                lockIcon.classList.remove('fa-unlock');
                lockIcon.classList.add('fa-lock');
            }
        });
    </script>
</body>
</html>
