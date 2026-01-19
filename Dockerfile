FROM php:8.2-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer extensions PHP nécessaires à Laravel
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    xml

WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/road-check

RUN composer install

CMD ["php-fpm"]
