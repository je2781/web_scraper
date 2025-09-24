# Use official PHP with FPM
FROM php:8.3-fpm

# Install system dependencies, clean up in one layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl procps \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    netcat-openbsd \
    default-mysql-client \
    gnupg2 ca-certificates wget xz-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy the application
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Fix permissions for Laravel storage & cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache  

# Expose php-fpm port
EXPOSE 9000

# Add wait-for-mysql script
COPY wait-for-mysql.sh /usr/local/bin/wait-for-mysql.sh
RUN chmod +x /usr/local/bin/wait-for-mysql.sh

CMD ["sh", "/usr/local/bin/wait-for-mysql.sh"]
