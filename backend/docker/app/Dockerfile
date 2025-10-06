FROM php:8.3-fpm-alpine
# pacotes e extensões
RUN apk add --no-cache git curl unzip libzip-dev oniguruma-dev bash \
&& docker-php-ext-install pdo_mysql mbstring zip
# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
