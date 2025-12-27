#!/bin/bash

# Script untuk update .env ke konfigurasi staging/production yang benar
# Usage: sudo ./update-env.sh

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Update .env Configuration ===${NC}\n"

# Get current directory
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$PROJECT_DIR" || exit 1

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    exit 1
fi

# Backup .env
BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"
cp .env "$BACKUP_FILE"
echo -e "${GREEN}✓ Backup created: ${BACKUP_FILE}${NC}\n"

# Ask for environment type
echo -e "${YELLOW}Select environment type:${NC}"
echo "1) Staging"
echo "2) Production"
read -p "Enter choice (1 or 2): " env_choice

if [ "$env_choice" = "2" ]; then
    APP_ENV_VALUE="production"
    LOG_LEVEL_VALUE="warning"
else
    APP_ENV_VALUE="staging"
    LOG_LEVEL_VALUE="info"
fi

echo -e "\n${GREEN}Updating .env...${NC}"

# Update APP_ENV
sed -i "s/^APP_ENV=.*/APP_ENV=${APP_ENV_VALUE}/" .env
echo -e "✓ APP_ENV=${APP_ENV_VALUE}"

# Update APP_DEBUG (always false for server)
sed -i "s/^APP_DEBUG=.*/APP_DEBUG=false/" .env
echo -e "✓ APP_DEBUG=false"

# Update LOG_LEVEL
sed -i "s/^LOG_LEVEL=.*/LOG_LEVEL=${LOG_LEVEL_VALUE}/" .env
echo -e "✓ LOG_LEVEL=${LOG_LEVEL_VALUE}"

# Update CACHE_STORE (to file for better performance)
if grep -q "^CACHE_STORE=database" .env; then
    read -p "Change CACHE_STORE from database to file? (y/n): " cache_choice
    if [ "$cache_choice" = "y" ]; then
        sed -i "s/^CACHE_STORE=.*/CACHE_STORE=file/" .env
        echo -e "✓ CACHE_STORE=file"
    fi
fi

# Ask about MAIL_MAILER
echo -e "\n${YELLOW}Current MAIL_MAILER: $(grep '^MAIL_MAILER=' .env | cut -d '=' -f2)${NC}"
read -p "Update MAIL_MAILER to smtp? (y/n): " mail_choice
if [ "$mail_choice" = "y" ]; then
    sed -i "s/^MAIL_MAILER=.*/MAIL_MAILER=smtp/" .env
    echo -e "✓ MAIL_MAILER=smtp"
    echo -e "${YELLOW}⚠️  Don't forget to configure SMTP settings in .env${NC}"
fi

# Clear Laravel caches
echo -e "\n${GREEN}Clearing Laravel caches...${NC}"
php artisan config:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear config${NC}"
php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}Warning: Could not clear cache${NC}"
php artisan optimize:clear 2>/dev/null || echo -e "${YELLOW}Warning: optimize:clear not available${NC}"

# Show summary
echo -e "\n${BLUE}=== Summary ===${NC}"
echo -e "${GREEN}✓ .env updated successfully!${NC}"
echo -e "${GREEN}✓ Backup saved as: ${BACKUP_FILE}${NC}"
echo -e "${GREEN}✓ Caches cleared${NC}"

echo -e "\n${YELLOW}Updated values:${NC}"
grep "^APP_ENV=" .env
grep "^APP_DEBUG=" .env
grep "^LOG_LEVEL=" .env
grep "^CACHE_STORE=" .env
grep "^MAIL_MAILER=" .env

echo -e "\n${BLUE}=== Next Steps ===${NC}"
echo -e "1. Review .env file: ${YELLOW}nano .env${NC}"
echo -e "2. Configure SMTP settings if MAIL_MAILER=smtp"
echo -e "3. Test the application"
echo -e "4. For production, run: ${YELLOW}php artisan config:cache${NC}"





