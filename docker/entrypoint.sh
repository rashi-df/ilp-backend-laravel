#!/bin/bash
set -e

echo "ILP Container Starting..."

fix_permissions() {
    mkdir -p /var/www/storage/logs \
             /var/www/storage/framework/cache \
             /var/www/storage/framework/sessions \
             /var/www/storage/framework/views \
             /var/www/storage/app/public \
             /var/www/bootstrap/cache
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache
    echo "Permissions set"
}

check_composer() {
    if [ ! -f "/var/www/vendor/autoload.php" ]; then
        echo "Installing Composer dependencies..."
        cd /var/www && composer install --optimize-autoloader --no-interaction
    fi
}

optimize_laravel() {
    cd /var/www
    php artisan config:clear  || true
    php artisan route:clear   || true
    php artisan view:clear    || true
    php artisan config:cache  || true
    echo "Laravel optimized"
}

fix_permissions
check_composer
optimize_laravel

echo "ILP Container Ready!"
exec "$@"
