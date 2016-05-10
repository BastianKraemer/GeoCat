FROM php:7.0.6-apache

RUN apt-get update && apt-get install -y vim dos2unix libmcrypt-dev libpq-dev \
    && docker-php-ext-install -j$(nproc) mcrypt pdo pdo_mysql pdo_pgsql

COPY docker_include/ /var/www/html/
COPY dest/ /var/www/html/

COPY src /var/geocat/src
COPY install /var/geocat/install
COPY test /var/geocat/test
COPY scripts /var/geocat/scripts

RUN dos2unix /var/geocat/scripts/*; chmod +x /var/geocat/scripts/*;
