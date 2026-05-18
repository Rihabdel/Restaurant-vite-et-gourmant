FROM php:8.3-apache

# 1. Configuration du dossier de travail dans le conteneur
WORKDIR /var/www/html

# 2. Installation des extensions nécessaires pour Symfony et MySQL
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# 3. Activation du module rewrite d'Apache (indispensable pour Symfony et ton Router JS)
RUN a2enmod rewrite

# 4. On force Apache à pointer sur le dossier public du backend
ENV APACHE_DOCUMENT_ROOT /var/www/html/BACKEND/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Récupération de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copie de TOUT le projet (BACKEND et FRONTEND) dans le conteneur
COPY . .

# 7. LA MAGIE DE LA FUSION : On déplace automatiquement le dossier FRONTEND dans le public du BACKEND
RUN cp -R /var/www/html/FRONTEND/* /var/www/html/BACKEND/public/

# 8. Droits d'accès pour Apache
RUN chown -R www-data:www-data /var/www/html