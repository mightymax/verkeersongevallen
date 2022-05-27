FROM php:8-fpm
RUN apt-get update && apt-get install -y \
  libpq-dev unzip \
  && docker-php-ext-install pdo pdo_pgsql
WORKDIR /app/
