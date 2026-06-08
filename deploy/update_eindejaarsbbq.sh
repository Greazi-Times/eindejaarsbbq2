#!/bin/bash
set -euo pipefail

PROJECT_DIR="/var/www/eindejaarsbbq"
BRANCH="main"

echo "Starting EindejaarsBBQ update..."

cd "$PROJECT_DIR" || { echo "Project directory not found: $PROJECT_DIR"; exit 1; }

php artisan down || true

restore_app() {
    php artisan up || true
}
trap restore_app EXIT

echo "Fetching latest code from GitHub..."
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
git clean -fd

echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Clearing old Laravel caches..."
php artisan optimize:clear

echo "Building frontend assets..."
npm ci
npm run build

echo "Running database migrations..."
php artisan migrate --force

echo "Linking storage..."
php artisan storage:link --force || true

echo "Optimizing Laravel..."
php artisan optimize

echo "Fixing Laravel writable directories..."
chown -R www-data:www-data "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"
chmod -R ug+rwX "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

echo "Reloading nginx..."
systemctl reload nginx

echo "EindejaarsBBQ successfully updated to origin/$BRANCH!"
