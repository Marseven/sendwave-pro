#!/bin/bash

# ===========================================
# Script de deploiement SendWave Pro
# Usage: bash deploy.sh
# ===========================================

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   SendWave Pro - Deploiement${NC}"
echo -e "${GREEN}========================================${NC}"

# Repertoire du projet
PROJECT_DIR="/var/www/sendwave-pro"
cd $PROJECT_DIR

echo -e "\n${YELLOW}[1/8] Pull des dernières modifications...${NC}"
git pull origin main

echo -e "\n${YELLOW}[2/8] Installation des dependances Composer...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

echo -e "\n${YELLOW}[3/8] Installation des dependances NPM...${NC}"
if command -v npm &> /dev/null; then
    npm ci --silent
    echo -e "\n${YELLOW}[4/8] Build du frontend...${NC}"
    npm run build
else
    echo -e "${YELLOW}NPM non installe, skip du build frontend${NC}"
fi

echo -e "\n${YELLOW}[5/8] Execution des migrations...${NC}"
php artisan migrate --force

echo -e "\n${YELLOW}[6/8] Nettoyage des caches...${NC}"
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
rm -rf bootstrap/cache/*.php 2>/dev/null || true

echo -e "\n${YELLOW}[7/8] Regeneration des caches...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "\n${YELLOW}[8/8] Permissions des fichiers...${NC}"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo -e "\n${YELLOW}Redemarrage PHP-FPM...${NC}"
systemctl restart php8.2-fpm 2>/dev/null || service php8.2-fpm restart 2>/dev/null || true

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}   Deploiement termine avec succes!${NC}"
echo -e "${GREEN}========================================${NC}"

# Afficher les infos importantes
echo -e "\n${YELLOW}Verification du .env:${NC}"
grep -E "^APP_URL=|^APP_ENV=|^APP_DEBUG=" .env

echo -e "\n${YELLOW}Version Laravel:${NC}"
php artisan --version

echo ""
