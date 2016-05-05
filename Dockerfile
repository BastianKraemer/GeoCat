FROM php:7.0.6-apache

RUN apt-get update && apt-get install -y libmcrypt-dev libpq-dev \
    && docker-php-ext-install -j$(nproc) mcrypt pdo pdo_mysql pdo_pgsql

COPY docker_include/ /var/www/html/
COPY src/ /var/www/html/
