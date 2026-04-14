#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/edcrm-saas"
BRANCH="${1:-main}"

echo "[deploy] app dir: ${APP_DIR}"
cd "${APP_DIR}"

echo "[deploy] fetching latest code"
git fetch origin "${BRANCH}"
git checkout "${BRANCH}"
git pull --ff-only origin "${BRANCH}"

echo "[deploy] installing composer dependencies"
composer install --no-dev --optimize-autoloader

if [ ! -f .env ]; then
  echo "[deploy] creating .env from env template"
  cp env .env
fi

echo "[deploy] running database migrations"
php spark migrate --all --no-interaction

echo "[deploy] fixing writable permissions"
sudo chown -R www-data:www-data "${APP_DIR}/writable"
sudo chmod -R 775 "${APP_DIR}/writable"

echo "[deploy] reloading services"
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx

echo "[deploy] done"
