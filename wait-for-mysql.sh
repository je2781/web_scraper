#!/bin/sh
# wait-for-pgsql.sh

set -e

host="admin_db"
port="3306"

until nc -z "$host" "$port"; do
  echo "Waiting for mysql at $host:$port..."
  sleep 2
done

# Fix Laravel permissions every time container starts
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Running migrations..."
composer migrate

echo "Running seeders..."
composer seed

echo "Importing profiles into Scout..."
composer import

# Start PHP-FPM
exec php-fpm