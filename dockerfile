FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    zip unzip libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/BACKEND/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Configuration Apache FORCÉE pour les routes API
RUN echo '<Directory /var/www/html/BACKEND/public>' >> /etc/apache2/apache2.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '    RewriteEngine On' >> /etc/apache2/apache2.conf && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /etc/apache2/apache2.conf && \
    echo '    RewriteRule ^api/.*$ /index.php [L]' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY BACKEND/ BACKEND/
COPY FRONTEND/ BACKEND/public/

RUN cd BACKEND && composer install --no-dev --optimize-autoloader --no-scripts

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN chown -R www-data:www-data BACKEND/var

EXPOSE 80
CMD ["apache2-foreground"]