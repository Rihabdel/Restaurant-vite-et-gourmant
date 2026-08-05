FROM php:8.3-apache
# installation des dépendances nécessaires à symfony et à l'extension pdo_pgsql
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
# Activation du module rewrite pour Apache
RUN a2enmod rewrite
# copie le fichier de configuration Apache personnalisé
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
#installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le projet Symfony
COPY . .
# Set environment variables for production
ENV APP_ENV=prod
ENV APP_DEBUG=0
# Installation des dépendances PHP 
RUN composer install --no-dev --optimize-autoloader  
#créer un utilaisateur pour exécuter le conteneur
RUN useradd -m appSymfony && chown -R appSymfony:appSymfony /var/www/html

# autoriser l'exécution du script de démarrage
RUN chmod +x docker/entrypoint.sh
# Changer l'utilisateur pour exécuter le conteneur
USER appSymfony
# Expose port 80 for the web server
EXPOSE 80

# Utilisation du script comme point d'entrée
ENTRYPOINT ["docker/entrypoint.sh"]