FROM php:8.2-fpm


# Install dependencies
RUN apt-get update && apt-get install -y \
    curl unzip zip nano \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
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

# Copy Laravel files
COPY . .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache 

# Change script to executable
RUN chmod +x /var/www/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/var/www/entrypoint.sh"]