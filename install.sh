
### Файл 2: `install.sh`
```bash
#!/bin/bash

# AmneziaWG Web Panel Installer
# Автоматическая установка веб-панели

set -e

echo "========================================="
echo "Установка AmneziaWG Web Panel"
echo "========================================="

# Проверка root прав
if [ "$EUID" -ne 0 ]; then 
    echo "Пожалуйста, запустите скрипт с sudo"
    exit 1
fi

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция вывода с цветом
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

# Обновление системы
print_status "Обновление пакетов системы..."
apt update && apt upgrade -y

# Установка зависимостей
print_status "Установка необходимых пакетов..."
apt install -y \
    php8.1 \
    php8.1-fpm \
    php8.1-cli \
    php8.1-json \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-gd \
    php8.1-curl \
    php8.1-zip \
    php8.1-bcmath \
    nginx \
    git \
    curl \
    wget \
    unzip \
    sudo

# Проверка установки Docker
if ! command -v docker &> /dev/null; then
    print_status "Установка Docker..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
else
    print_status "Docker уже установлен"
fi

# Проверка установки Docker Compose
if ! command -v docker-compose &> /dev/null; then
    print_status "Установка Docker Compose..."
    apt install -y docker-compose-plugin
fi

# Создание директории для панели
PANEL_DIR="/var/www/amnezia-panel"
print_status "Создание директории панели: $PANEL_DIR"
mkdir -p $PANEL_DIR

# Копирование файлов панели
print_status "Копирование файлов панели..."
cp -r ./* $PANEL_DIR/

# Настройка прав доступа
print_status "Настройка прав доступа..."
chown -R www-data:www-data $PANEL_DIR
chmod -R 755 $PANEL_DIR
chmod 775 $PANEL_DIR/data $PANEL_DIR/backups 2>/dev/null || true

# Создание необходимых директорий
mkdir -p $PANEL_DIR/data $PANEL_DIR/backups $PANEL_DIR/logs
touch $PANEL_DIR/data/clients.json
echo "[]" > $PANEL_DIR/data/clients.json

# Настройка PHP
print_status "Настройка PHP..."
PHP_INI="/etc/php/8.1/fpm/php.ini"
if [ -f $PHP_INI ]; then
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' $PHP_INI
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' $PHP_INI
    sed -i 's/^post_max_size = .*/post_max_size = 50M/' $PHP_INI
    sed -i 's/^max_execution_time = .*/max_execution_time = 300/' $PHP_INI
    sed -i 's/^;date.timezone =/date.timezone = Europe\/Moscow/' $PHP_INI
fi

# Настройка Nginx
print_status "Настройка Nginx..."
NGINX_CONF="/etc/nginx/sites-available/amnezia-panel"

cat > $NGINX_CONF << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/amnezia-panel;
    index index.php index.html;
    
    client_max_body_size 50M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location ~ /(data|backups|includes)/.*\.php$ {
        deny all;
        return 403;
    }
    
    access_log /var/log/nginx/amnezia-panel.access.log;
    error_log /var/log/nginx/amnezia-panel.error.log;
}
EOF

# Активация конфигурации Nginx
ln -sf $NGINX_CONF /etc/nginx/sites-enabled/
nginx -t

# Перезапуск сервисов
print_status "Перезапуск сервисов..."
systemctl restart php8.1-fpm
systemctl restart nginx

# Настройка sudo для безопасного выполнения команд
print_status "Настройка прав sudo для веб-сервера..."
SUDOERS_FILE="/etc/sudoers.d/amnezia-panel"
cat > $SUDOERS_FILE << 'EOF'
# Разрешить пользователю www-data выполнять необходимые команды без пароля
www-data ALL=(root) NOPASSWD: /usr/bin/docker compose *
www-data ALL=(root) NOPASSWD: /bin/tar *
www-data ALL=(root) NOPASSWD: /bin/systemctl restart docker
EOF

chmod 440 $SUDOERS_FILE

# Настройка cron для автоматических задач
print_status "Настройка автоматических задач..."
CRON_FILE="/etc/cron.d/amnezia-panel"
cat > $CRON_FILE << 'EOF'
# Ежедневное резервное копирование в 3:00
0 3 * * * www-data cd /var/www/amnezia-panel && /usr/bin/php -f cron/backup.php > /dev/null 2>&1

# Очистка старых логов раз в неделю
0 4 * * 0 www-data find /var/www/amnezia-panel/logs -name "*.log" -mtime +30 -delete

# Проверка статуса каждый час
0 * * * * www-data cd /var/www/amnezia-panel && /usr/bin/php -f cron/check_status.php > /dev/null 2>&1
EOF

# Создание файла конфигурации, если его нет
if [ ! -f "$PANEL_DIR/config.php" ]; then
    print_status "Создание файла конфигурации..."
    cp "$PANEL_DIR/config.default.php" "$PANEL_DIR/config.php" 2>/dev/null || true
fi

# Финальное сообщение
echo ""
echo "========================================="
echo "Установка завершена успешно!"
echo "========================================="
echo ""
echo "📋 Информация:"
echo "• Панель доступна по адресу: http://$(curl -s ifconfig.me)/"
echo "• Логин по умолчанию: admin"
echo "• Пароль по умолчанию: admin123"
echo ""
echo "🔐 Обязательно измените пароль в файле:"
echo "   $PANEL_DIR/config.php"
echo ""
echo "📁 Директория панели: $PANEL_DIR"
echo "📝 Логи Nginx: /var/log/nginx/amnezia-panel.*.log"
echo ""
echo "⚠️  Для безопасности:"
echo "   1. Настройте HTTPS (Let's Encrypt)"
echo "   2. Измените пароль администратора"
echo "   3. Ограничьте доступ по IP"
echo ""
echo "Удачи! 🚀"
