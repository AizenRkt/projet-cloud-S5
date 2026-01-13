FROM php:8.2-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Installer extensions PHP nécessaires à Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

# Nettoyage
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
