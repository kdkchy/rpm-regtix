#!/bin/bash

# Script lengkap untuk fix semua masalah deployment Laravel
# Usage: sudo ./deploy-fix.sh

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Laravel Deployment Fix Script ===${NC}\n"

# Detect web server user
if id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
else
    echo -e "${RED}Error: Cannot detect web server user. Please set WEB_USER manually.${NC}"
    exit 1
fi

echo -e "${YELLOW}Detected web server user: ${WEB_USER}${NC}\n"

# Get current directory (assuming script is in project root)
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo -e "${YELLOW}Project directory: ${PROJECT_DIR}${NC}\n"

# Navigate to project directory
cd "$PROJECT_DIR" || exit 1

# 1. Fix permissions
echo -e "${GREEN}[1/6] Fixing permissions...${NC}"
if [ -f "fix-permissions.sh" ]; then
    sudo chmod +x fix-permissions.sh
    sudo ./fix-permissions.sh
else
    echo -e "${YELLOW}fix-permissions.sh not found, setting permissions manually...${NC}"
    sudo chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    sudo touch storage/logs/laravel.log
    sudo chmod 664 storage/logs/laravel.log
    sudo chown "$WEB_USER:$WEB_USER" storage/logs/laravel.log
fi
echo -e "${GREEN}✓ Permissions fixed${NC}\n"

# 2. Clear all caches
echo -e "${GREEN}[2/6] Clearing Laravel caches...${NC}"
php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear cache${NC}"
php artisan config:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear config${NC}"
php artisan route:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear route${NC}"
php artisan view:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear view${NC}"
php artisan optimize:clear 2>/dev/null || echo -e "${YELLOW}Warning: optimize:clear not available${NC}"
echo -e "${GREEN}✓ Caches cleared${NC}\n"

# 3. Update composer
echo -e "${GREEN}[3/6] Updating composer dependencies...${NC}"
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader 2>/dev/null || composer install --optimize-autoloader
    composer dump-autoload 2>/dev/null || echo -e "${YELLOW}Warning: Could not dump autoload${NC}"
    echo -e "${GREEN}✓ Composer updated${NC}\n"
else
    echo -e "${YELLOW}Warning: Composer not found, skipping...${NC}\n"
fi

# 4. Storage link
echo -e "${GREEN}[4/6] Creating storage symlink...${NC}"
php artisan storage:link 2>/dev/null || echo -e "${YELLOW}Warning: Storage link already exists or failed${NC}"
echo -e "${GREEN}✓ Storage link created${NC}\n"

# 5. Check .env file
echo -e "${GREEN}[5/6] Checking .env file...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}Warning: .env file not found${NC}"
    if [ -f ".env.example" ]; then
        echo -e "${YELLOW}Copying .env.example to .env...${NC}"
        cp .env.example .env
        php artisan key:generate 2>/dev/null || echo -e "${YELLOW}Warning: Could not generate key${NC}"
    fi
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi
echo ""

# 6. Restart PHP-FPM
echo -e "${GREEN}[6/6] Restarting PHP-FPM...${NC}"
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "8.3")
if systemctl is-active --quiet "php${PHP_VERSION}-fpm" 2>/dev/null; then
    sudo systemctl restart "php${PHP_VERSION}-fpm" 2>/dev/null || sudo service "php${PHP_VERSION}-fpm" restart 2>/dev/null
    echo -e "${GREEN}✓ PHP-FPM restarted${NC}\n"
elif systemctl is-active --quiet php-fpm 2>/dev/null; then
    sudo systemctl restart php-fpm 2>/dev/null || sudo service php-fpm restart 2>/dev/null
    echo -e "${GREEN}✓ PHP-FPM restarted${NC}\n"
else
    echo -e "${YELLOW}PHP-FPM not found or not running, skipping restart${NC}\n"
fi

# Summary
echo -e "${BLUE}=== Summary ===${NC}"
echo -e "${GREEN}✓ Permissions fixed${NC}"
echo -e "${GREEN}✓ Caches cleared${NC}"
echo -e "${GREEN}✓ Composer updated${NC}"
echo -e "${GREEN}✓ Storage link created${NC}"
echo -e "${GREEN}✓ .env checked${NC}"
echo -e "${GREEN}✓ PHP-FPM restarted${NC}"

echo -e "\n${BLUE}=== Deployment fix completed! ===${NC}"
echo -e "${YELLOW}If you still encounter issues:${NC}"
echo -e "1. Check Laravel log: tail -f storage/logs/laravel.log"
echo -e "2. Check web server log: sudo tail -f /var/log/nginx/error.log"
echo -e "3. Verify .env configuration"
echo -e "4. Run migrations: php artisan migrate --force"





