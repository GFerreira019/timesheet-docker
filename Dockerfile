FROM php:8.2-fpm

# Instala as dependências do sistema necessárias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instala as extensões do PHP
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip

# Copia o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www

# Copia o código da aplicação (necessário para ajustar as permissões depois)
COPY . /var/www

# Ajusta as permissões das pastas para o usuário www-data
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
