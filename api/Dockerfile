FROM php:8.3-fpm-alpine

# Dépendances système
RUN apk add --no-cache git unzip bash libzip-dev icu-dev openssl-dev autoconf g++ make linux-headers

# Extensions PHP
RUN docker-php-ext-install intl zip opcache
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/api

# Entrypoint qui fixe les permissions à chaque démarrage
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]