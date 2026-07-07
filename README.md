# 📚 Knowledge Learning - Plateforme E-learning

## Description
Knowledge Learning est une plateforme e-learning/e-commerce développée avec Symfony 8.1.

## Prérequis
- PHP 8.1+
- Composer
- MySQL 8.0+
- Symfony CLI
- Compte Stripe (sandbox)
- Compte Mailtrap

## Installation

### 1. Cloner le projet
```bash
git clone https://github.com/DsRiri/knowledge-learning.git
cd knowledge-learning
2. Installer les dépendances
bash
composer install
3. Configurer l'environnement
bash
cp .env .env.local
# Modifier DATABASE_URL dans .env.local avec vos identifiants MySQL
4. Créer la base de données
bash
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load
5. Lancer le serveur
bash
symfony server:start
6. Accéder au site
Ouvrez votre navigateur et allez sur : http://127.0.0.1:8000

Compte admin (préchargé)
Email	Mot de passe
admin@knowledge.com	Admin123!
Tests
bash
vendor/bin/phpunit
Technologies utilisées
Symfony 8.1

Doctrine ORM

MySQL

Twig

Stripe API

PHPUnit


