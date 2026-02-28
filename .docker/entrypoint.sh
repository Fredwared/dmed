#!/bin/sh
set -e

echo "── Waiting for PostgreSQL ──"
until php -r "try { new PDO('pgsql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:'5432').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (\Throwable) {exit(1);}" 2>/dev/null; do
    sleep 2
done

echo "── Waiting for Redis ──"
until php -r "\$r=new Redis();\$r->connect(getenv('REDIS_HOST'),(int)(getenv('REDIS_PORT')?:'6379'));\$r->ping();" 2>/dev/null; do
    sleep 2
done

echo "── Running migrations ──"
php artisan migrate --force

echo "── Creating S3 bucket ──"
php artisan app:create-bucket

echo "── Caching config ──"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "── Storage link ──"
php artisan storage:link 2>/dev/null || true

echo "── Ready ──"
exec php-fpm
