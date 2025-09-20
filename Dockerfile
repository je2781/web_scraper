# Use official PHP with FPM
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    netcat-openbsd \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Now copy the application
COPY . .

# Install Laravel + Horizon before copying full app
RUN composer install --no-dev --optimize-autoloader \
    && composer require laravel/horizon


# Fix permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose php-fpm port
EXPOSE 9000

COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

CMD ["sh", "/usr/local/bin/wait-for-mysql.sh"]
