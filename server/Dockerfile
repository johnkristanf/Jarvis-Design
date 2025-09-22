FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl unzip zip nano \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libonig-dev libxml2-dev libzip-dev libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        zip \
        exif \
        pcntl \
        gd \
        xml

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies (this will verify extensions work)
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy the rest of the application
COPY . .

# Complete the composer setup
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache 

# Change script to executable
RUN chmod +x /var/www/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/var/www/entrypoint.sh"]