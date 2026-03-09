FROM php:8.3-apache

# 1. Dossier de travail standard Apache
WORKDIR /var/www/html

# 2. Extensions nécessaires pour Symfony et MySQL
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# 3. Activation du module de réécriture (Crucial pour les routes Symfony)
RUN a2enmod rewrite

# 4. On force Apache à pointer sur le dossier /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copie du code et droits d'accès
COPY . .
RUN chown -R www-data:www-data /var/www/html