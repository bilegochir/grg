#!/bin/sh
set -eu

cd /var/www/html

chmod -R ug+rwx storage bootstrap/cache

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
until php -r '
try {
    new PDO(
        sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_DATABASE")),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD")
    );
} catch (Throwable $e) {
    exit(1);
}
'; do
    sleep 2
done

php artisan config:clear
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port=8000
