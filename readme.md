**Application de gestion et de commande pour un service traiteur, réalisée avec Symfony 7 (Architecture API) et un Frontend dynamique (SPA).**

---

### 📋 Project requirements (Prérequis)

- [PHP >= 8.2](http://php.net/manual/fr/install.php) _(Recommandé : PHP 8.3)_
- [MySQL >= 8.0](https://www.mysql.com/fr/downloads/) ou MariaDB équivalent
- [MongoDB >= 6.0](https://www.mongodb.com/) _(Pour le module de statistiques)_
- [Symfony CLI](https://symfony.com/download)
- [Composer](https://getcomposer.org/download)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

- **PHP Extensions :** `pdo_mysql`, `mongodb`, `intl`, `iconv`, `session`.

````bash
# Pour vérifier la conformité de votre environnement local :
$ symfony check:requirements

### Installation

1 .Clone the current repository (SSH):

```bash
$ git clone 'https://github.com/Rihabdel/Restaurant-vite-et-gourmant'
````

2 . Move in and create few `.env.{environment}.local` files, according to your environments with your default configuration.
**.local files are not committed to the shared repository.**

```bash
$ cp .env .env.local   # Create .env.$APP_ENV.local files. Complete them with your configuration.
```

3 . Initialisation avec Docker :
Le projet utilise Docker pour isoler les services MySQL (données relationnelles) et MongoDB (statistiques).

```bash
$ docker-compose up -d
```

4. Dépendances et Base de données

```bash
$ composer install        # Install all PHP packages
$ php bin/console d:d:c   # Create your DATABASE related to your .env.local configuration
$ php bin/console d:m:m   # Run migrations to setup your DATABASE according to your entities
```

5.Usage (Lancement en local)

```bash
$ symfony local:server:start -d
```

## Sécurité

- Authentification via Header : `X-AUTH-TOKEN`
- Firewall Stateless configuré dans `security.yaml`.

## Rôles

- **Admin :** Gestion totale + Statistiques.
- **Employé :** Gestion commandes et avis.
- **Utilisateur :** Passage de commande et historique.

## Usage

```bash
$  php -S localhost:8000
```

To see all available routes, services... :

```bash
$ bin/console debug:router
$ bin/console debug:container
$ bin/console debug:...
```

## Continuous deployment

Le projet est configuré pour un déploiement automatisé et continu sur la plateforme Render :

    Automatisation : Chaque git push sur la branche principale déclenche un nouveau build.

    Backend : Déployé via un environnement conteneurisé utilisant le Dockerfile du projet.

## Architecture Front-end (SPA)

Le front-end est conçu comme une **Single Page Application** utilise un Router JavaScript sur mesure :

- **Router dynamique :** Charge les composants HTML et les scripts JS associés selon l'URL.
- **Contrôle d'accès :** Les routes sont protégées par rôle (`ROLE_USER`, `ROLE_ADMIN`) directement côté client pour améliorer l'expérience utilisateur (UX).
- **SEO & UX :** Gestion des titres de pages dynamiques et page 404 personnalisée.
