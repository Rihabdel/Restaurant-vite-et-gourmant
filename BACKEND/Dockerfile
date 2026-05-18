FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql intl zip

RUN a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN php bin/console cache:clear --env=prod
RUN php bin/console cache:warmup --env=prod

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]