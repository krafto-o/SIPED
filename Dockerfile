FROM php:8.3-apache

RUN apt-get update && apt-get install -y git libzip-dev zip && \
    docker-php-ext-install pdo pdo_mysql zip

RUN a2enmod rewrite headers

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

RUN chown -R www-data:www-data /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

