# Stage 1: Build Frontend Assets (Node.js)
FROM node:24-alpine as frontend

WORKDIR /app

# Copia arquivos de dependência do frontend
COPY package.json package-lock.json* vite.config.js tailwind.config.js postcss.config.js ./
RUN npm install

# Copia os resources (JS, CSS, Vue/Blade)
COPY resources/ ./resources/
COPY public/ ./public/

# Builda os assets
RUN npm run build

# =========================================================

# Stage 2: Instala Dependências PHP (Composer)
FROM composer:2.7 as vendor

WORKDIR /app

# Copia os arquivos necessários para o composer
COPY database/ ./database/
COPY composer.json composer.lock* ./

# Instala as dependências de produção do PHP
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-scripts

# =========================================================

# Stage 3: Runtime de Produção (PHP-FPM + Nginx + Supervisor)
FROM php:8.3-fpm-alpine as runtime

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala pacotes do sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-client \
    curl-dev \
    libxml2-dev \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    git \
    unzip \
    bash \
    netcat-openbsd

# Instala extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        mbstring \
        bcmath \
        zip \
        opcache \
        gd \
        intl \
        curl \
        xml

# Instala a extensão redis (via pecl)
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Copia configurações do Nginx e Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Cria diretórios necessários para o nginx e supervisor
RUN mkdir -p /run/nginx /var/run/php-fpm /var/log/supervisor

# Copia o código da aplicação
COPY . .

# Copia as dependências (vendor) geradas no Stage 2
COPY --from=vendor /app/vendor/ ./vendor/

# Copia os assets compilados (public/build) gerados no Stage 1
COPY --from=frontend /app/public/build/ ./public/build/

# Dá permissão nas pastas de storage e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configura o entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

# Utiliza o entrypoint para verificar banco e executar rotinas iniciais
ENTRYPOINT ["docker-entrypoint.sh"]
