FROM php:8.2-fpm

# =========================
# Dépendances système
# =========================
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure pgsql \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# =========================
# Composer
# =========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# =========================
# Copier projet
# =========================
COPY . .

# =========================
# Créer dossiers Laravel AVANT chmod
# =========================
RUN mkdir -p storage \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# =========================
# Installer dépendances
# =========================
RUN composer install --no-dev --optimize-autoloader
# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/road-check

RUN composer install

CMD ["php-fpm"]
