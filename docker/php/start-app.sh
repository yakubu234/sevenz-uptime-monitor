#!/bin/sh

set -e

cd /var/www/html

git config --global --add safe.directory /var/www/html || true

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

until php artisan migrate --force; do
    echo "Waiting for MySQL to become available..."
    sleep 3
done

php artisan serve --host=0.0.0.0 --port=8000
