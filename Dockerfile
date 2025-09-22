# Use official PHP with FPM
FROM php:8.3-fpm

# Install system dependencies & Browsershot libs, clean up in one layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl procps \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    netcat-openbsd \
    default-mysql-client \
    gnupg2 ca-certificates wget xz-utils \
    libx11-xcb1 libxcomposite1 libxcursor1 libxdamage1 libxi6 \
    libxtst6 libnss3 libxrandr2 libasound2 libpangocairo-1.0-0 \
    libcups2 libatk1.0-0 libatk-bridge2.0-0 libgtk-3-0 libgbm1 libdrm2 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js & Puppeteer, then clean up npm cache
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g puppeteer \
    && npm cache clean --force \
    && rm -rf /tmp/* /var/tmp/*

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
