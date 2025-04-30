FROM php:8.1-fpm

RUN apt update -y

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

RUN pecl install opentelemetry \
    && docker-php-ext-enable opentelemetry