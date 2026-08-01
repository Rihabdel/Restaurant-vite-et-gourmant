#!/bin/sh
set -e

# Exécution des migrations PostgreSQL
php bin/console doctrine:migrations:migrate --no-interaction

# Nettoyage et réchauffage du cache prod
php bin/console cache:clear

# Démarrage d'Apache
exec apache2-foreground