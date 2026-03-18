<<<<<<< HEAD
# ─── Dockerfile ─────────────────────────────────────────────
FROM php:8.2-apache

# ── System dependencies ──────────────────────────────────────
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# ── PHP Extensions ───────────────────────────────────────────
# GD (needed for mPDF image processing)
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip

# MongoDB extension from PECL
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Apache config ────────────────────────────────────────────
# Enable mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# ── App files ────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy entire project
COPY . .

# Install PHP dependencies
RUN cd backend && composer install --no-dev --optimize-autoloader

# Create uploads directory with correct permissions
RUN mkdir -p backend/uploads \
    && chmod -R 777 backend/uploads \
    && mkdir -p backend/tmp \
    && chmod -R 777 backend/tmp

# ── Apache VirtualHost ───────────────────────────────────────
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# ── PHP config ───────────────────────────────────────────────
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# ── Startup script ───────────────────────────────────────────
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
=======
# Use official PHP with Apache
FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libssl-dev pkg-config \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html/frontend/public

# Copy dependency files first
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy the rest of the app
COPY . .

# Expose Apache port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
>>>>>>> 62dc9aeeb48a64f2d3b5fca92c21dfb6e05c4b90
