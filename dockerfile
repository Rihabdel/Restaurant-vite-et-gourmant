FROM php:8.3-apache

# 1. Configuration du dossier de travail dans le conteneur
WORKDIR /var/www/html

# 2. Installation des extensions nécessaires pour Symfony et MySQL
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql intl zip

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
RUN cp -R /var/www/html/FRONTEND/. /var/www/html/BACKEND/public/
# ÉTAPE 7.5 : ON FORCE LA COPIE DU HTACCESS DU FRONT
RUN cp /var/www/html/FRONTEND/.htaccess /var/www/html/BACKEND/public/.htaccess

RUN git config --global --add safe.directory /var/www/html
RUN git config --global --add safe.directory /var/www/html/BACKEND

# 🔥 AJOUT 2 : Supprimer vendor et composer.lock pour éviter les conflits
RUN rm -rf /var/www/html/BACKEND/vendor /var/www/html/BACKEND/composer.lock

# 8. L'ÉTAPE CRUCIALE POUR RENDER : On va dans le dossier BACKEND et on installe les dépendances
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
# 8. Installation des dépendances (sans exécuter les scripts)
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
RUN cd /var/www/html/BACKEND && composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# 8.5 Exécuter les scripts manuellement
RUN cd /var/www/html/BACKEND && composer run-script post-install-cmd --no-interaction || true
RUN cd /var/www/html/BACKEND && php bin/console cache:clear --env=prod --no-debug || true

# 9. Config Apache pour servir index.html en priorité
RUN echo "DirectoryIndex index.html index.php" > /etc/apache2/conf-available/directory-index.conf \
    && a2enconf directory-index