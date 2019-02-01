FROM composer:1.7 as composer

FROM php:7.2.8-fpm-alpine

RUN apk update && apk add \
    openssh \
    bash

RUN docker-php-ext-install \
    mbstring \
    pdo \
    pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
