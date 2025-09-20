#!/bin/sh
# wait-for-pgsql.sh

set -e

host="admin_db"
port="3306"

until nc -z "$host" "$port"; do
  echo "Waiting for mysql at $host:$port..."
  sleep 2
done

echo "Running migrations..."
composer migrate

echo "Running seeders..."
composer seed

# Start PHP-FPM
exec php-fpm