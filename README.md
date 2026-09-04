# MotoRide Connect

Réseau social pour motards : chaque membre gère son garage personnel et organise ou rejoint des balades avec d'autres passionnés.

Projet réalisé dans le cadre du titre professionnel DWWM.

## Fonctionnalités

- **Profil utilisateur** : page d'édition privée et page publique consultable par les autres membres
- **Garage moto** : ajout, modification et suppression de motos (marque, modèle, photo) rattachées à son profil
- **Balades** : création d'une balade, participation avec gestion de la capacité, commentaires
- **Modération** : système de signalement (strikes) et back-office admin

## Stack technique

- **Backend** : Symfony 7.4 / PHP 8.2
- **ORM** : Doctrine (MySQL 8.4)
- **Environnement** : Docker Compose (php-fpm, nginx, MySQL, phpMyAdmin)
- **Traitement d'images** : GD (redimensionnement et conversion WebP à l'upload)

## Installation locale

### Prérequis

- Docker et Docker Compose

### Étapes

1. Copier le fichier d'environnement et renseigner les secrets :

   ```bash
   cp .env.example .env
   ```

   Compléter au minimum `APP_SECRET` et `MYSQL_PASSWORD` (le même mot de passe doit être utilisé dans `DATABASE_URL`).

2. Lancer les conteneurs :

   ```bash
   make up
   ```

   ou directement :

   ```bash
   docker compose up -d
   ```

3. Installer les dépendances PHP et exécuter les migrations :

   ```bash
   docker compose exec php composer install
   docker compose exec php bin/console doctrine:migrations:migrate
   ```

4. L'application est accessible sur le port exposé par le service `nginx`, phpMyAdmin sur celui du service `phpmyadmin`.

## Licence

Projet propriétaire.
