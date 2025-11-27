# Wiki Guessr

Un projet Symfony 7.4 avec Docker et FrankenPHP.

## Prérequis

- Docker et Docker Compose
- Accès sudo pour modifier `/etc/hosts`

## Installation

### 1. Configuration du domaine local

Ajoutez le domaine local dans votre fichier `/etc/hosts` :

```bash
sudo nano /etc/hosts
```

Ajoutez cette ligne :

```
127.0.0.1 wiki-guessr.local
```

### 2. Démarrage des conteneurs

```bash
# Se placer dans le dossier config
cd config

# Démarrer tous les services
docker compose up -d

# Voir les logs
docker compose logs -f

# Arrêter les services
docker compose down
```

### 3. Accès à l'application

L'application est accessible à l'adresse :
- **HTTP** : http://wiki-guessr.local:8080
- **HTTPS** : https://wiki-guessr.local:8443

### 4. Base de données

La base de données MySQL est accessible sur le port **3307** :

- **Host** : localhost (depuis la machine hôte) ou `database` (depuis les conteneurs)
- **Port** : 3307 (depuis la machine hôte) ou 3306 (depuis les conteneurs)
- **Database** : wiki_guessr
- **User** : wiki_guessr
- **Password** : wiki_guessr_password
- **Root Password** : root_password

Les données MySQL sont stockées dans le dossier `mysql/` à la racine du projet.

## Commandes utiles

**Note** : Toutes les commandes docker compose doivent être exécutées depuis le dossier `config/`

### Composer

```bash
# Installer les dépendances
cd config && docker compose run --rm frankenphp composer install

# Ajouter un package
cd config && docker compose run --rm frankenphp composer require nom/du-package

# Mettre à jour les dépendances
cd config && docker compose run --rm frankenphp composer update
```

### Symfony Console

```bash
# Exécuter une commande Symfony
cd config && docker compose exec frankenphp bin/console [commande]

# Exemples :
cd config && docker compose exec frankenphp bin/console about
cd config && docker compose exec frankenphp bin/console cache:clear
cd config && docker compose exec frankenphp bin/console debug:router
```

### Base de données

```bash
# Créer la base de données
cd config && docker compose exec frankenphp bin/console doctrine:database:create

# Créer les migrations
cd config && docker compose exec frankenphp bin/console make:migration

# Exécuter les migrations
cd config && docker compose exec frankenphp bin/console doctrine:migrations:migrate
```

## Structure du projet

```
.
├── config/                 # Configuration Docker
│   ├── Caddyfile          # Configuration du serveur Caddy/FrankenPHP
│   ├── docker-compose.yml # Configuration Docker Compose
│   ├── Dockerfile         # Image Docker personnalisée
│   └── .dockerignore      # Fichiers ignorés par Docker
├── html/                   # Application Symfony
│   ├── bin/               # Scripts exécutables (console Symfony)
│   ├── config/            # Configuration de l'application
│   ├── public/            # Point d'entrée web (index.php)
│   ├── src/               # Code source de l'application
│   ├── var/               # Fichiers générés (cache, logs)
│   ├── vendor/            # Dépendances Composer
│   └── .env               # Variables d'environnement
├── mysql/                  # Données de la base de données MySQL
└── README.md              # Ce fichier
```

## Développement

### Ports utilisés

- **8080** : HTTP (FrankenPHP)
- **8443** : HTTPS (FrankenPHP)
- **3307** : MySQL

Ces ports ont été choisis pour éviter les conflits avec d'autres services Docker existants.

### Organisation des dossiers

- **config/** : Contient tous les fichiers de configuration Docker
- **html/** : Contient le code source de l'application Symfony
- **mysql/** : Stocke les données de la base de données MySQL (créé automatiquement au premier lancement)