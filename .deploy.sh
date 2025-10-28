#!/bin/bash
echo "=== Début du déploiement Hostinger ==="

# Aller dans le répertoire du projet
cd /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign/

# Mise à jour de Composer
echo "Mise à jour des dépendances PHP..."
composer update --no-dev --optimize-autoloader

# Exécution des migrations
echo "Exécution des migrations de base de données..."
php artisan migrate --force

# Nettoyage et optimisation
echo "Optimisation de l'application..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Déploiement terminé avec succès!"
