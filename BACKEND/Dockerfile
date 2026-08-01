FROM php:8.3-apache
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    intl
# Enable Apache mod_rewrite
RUN a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN composer install --no-dev --optimize-autoloader  


EXPOSE 80
# AUTOMATISATION DES MIGRATIONS AU DÉMARRAGE DU CONTENEUR
RUN php bin/console doctrine:migrations:migrate --no-interaction