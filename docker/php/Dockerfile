FROM php:8.3-fpm

# Thư viện hệ thống cần cho các extension PHP
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Extension PHP mà Laravel và dự án cần
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip exif pcntl

# Extension Redis
RUN pecl install redis && docker-php-ext-enable redis

# Composer (lấy từ image chính thức)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN docker compose -f docker-compose.local.yml exec -u root app chown -R www-data:www-data storage bootstrap/cache
RUN docker compose -f docker-compose.local.yml exec -u root app chmod -R 775 storage bootstrap/cache