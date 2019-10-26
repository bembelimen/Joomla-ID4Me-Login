# Dockerfile
FROM php:7.0-apache

RUN apt-get update \
    && apt-get install -y \
        git \
        curl \
        nano \
        unzip \
        netcat

RUN ln -s /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD . /var/www/html/

RUN chown -R www-data:www-data /var/www

RUN cd /var/www/html/ && composer install --prefer-dist
