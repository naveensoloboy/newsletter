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
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip

# MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# ── Composer ─────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Apache config ────────────────────────────────────────────
RUN a2enmod rewrite headers

# ── App files ────────────────────────────────────────────────
WORKDIR /var/www/html

COPY . .

# Install backend dependencies
RUN cd backend && composer install --no-dev --optimize-autoloader

# Create required folders
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