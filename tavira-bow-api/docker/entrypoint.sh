#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-bow}" -q; do
    sleep 1
done
echo "PostgreSQL is ready."

echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

echo "Starting server..."
exec php artisan serve --host=0.0.0.0 --port=8000
