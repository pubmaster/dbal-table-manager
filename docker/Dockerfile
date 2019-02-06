FROM composer:1.7 as composer

FROM php:7.2.8-fpm-alpine

RUN apk update && apk add \
    openssh \
    bash

RUN set -ex && apk --no-cache add postgresql-dev

RUN docker-php-ext-install \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
