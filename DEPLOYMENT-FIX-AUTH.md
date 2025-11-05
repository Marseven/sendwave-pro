# Fix d'Authentification Multi-Navigateurs/Périphériques

## Problème identifié

L'authentification fonctionne sur Chrome mais pas sur d'autres navigateurs/périphériques en raison d'une configuration CORS inadéquate.

## Solution

### 1. Modifications apportées

#### a) Configuration CORS (`config/cors.php`)
- ✅ Activé `supports_credentials => true` (ligne 32)
- ✅ Changé `allowed_origins` pour utiliser la variable d'environnement `CORS_ALLOWED_ORIGINS`
- ✅ Ajouté `Authorization` aux `exposed_headers`
- ✅ Augmenté `max_age` à 86400 secondes (24h)

#### b) Variables d'environnement (`.env`)
Ajout de deux nouvelles variables :

```env
# CORS Configuration (séparés par des virgules)
CORS_ALLOWED_ORIGINS=https://lightgreen-otter-916987.hostingersite.com,http://mysmscampaign.jobs-conseil.com,https://mysmscampaign.jobs-conseil.com

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=lightgreen-otter-916987.hostingersite.com,mysmscampaign.jobs-conseil.com
```

### 2. Déploiement sur Hostinger

#### Étape 1 : Connexion SSH
```bash
ssh u663389624@uk-fast-web1375.main-hosting.eu
cd /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign
```

#### Étape 2 : Récupérer les modifications
```bash
git pull origin main
```

#### Étape 3 : Copier le fichier .env de production
```bash
# Sauvegarder l'ancien .env (optionnel)
cp .env .env.backup

# Utiliser le nouveau fichier de production
cp .env.production .env
```

#### Étape 4 : Modifier les variables sensibles dans .env
Éditer le fichier `.env` et remplacer :
- `DB_PASSWORD=YOUR_DB_PASSWORD` par le vrai mot de passe de la base de données
- `APP_KEY=` si nécessaire (exécuter `php artisan key:generate`)
- Les clés API SMS si disponibles

```bash
nano .env
# ou
vi .env
```

#### Étape 5 : Vérifier la configuration
```bash
# Vérifier que les domaines sont corrects
grep CORS_ALLOWED_ORIGINS .env
grep SANCTUM_STATEFUL_DOMAINS .env
grep APP_URL .env
```

#### Étape 6 : Lancer le déploiement
```bash
bash .deploy.sh
```

#### Étape 7 : Permissions (si nécessaire)
```bash
chmod -R 755 storage bootstrap/cache
chown -R u663389624:u663389624 storage bootstrap/cache
```

### 3. Configuration additionnelle pour la production

#### a) Vérifier le fichier .htaccess dans `/public`
Le fichier devrait contenir :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# CORS Headers
<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Authorization, Content-Type, Accept"
    Header always set Access-Control-Expose-Headers "Authorization"
    Header always set Access-Control-Max-Age "86400"
</IfModule>
```

#### b) Vérifier que le Document Root pointe vers `/public`
Dans le panneau Hostinger :
- Domaines > Gérer > `lightgreen-otter-916987.hostingersite.com`
- Document Root doit être : `/home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign/public`

### 4. Tests post-déploiement

1. **Vider le cache Laravel**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

2. **Tester l'API depuis différents navigateurs**
- Chrome
- Firefox
- Safari
- Edge
- Mobile (iOS/Android)

3. **Vérifier les headers CORS**
Ouvrir la console du navigateur (F12) et vérifier les headers de réponse :
```
Access-Control-Allow-Origin: https://lightgreen-otter-916987.hostingersite.com
Access-Control-Allow-Credentials: true
```

4. **Test de connexion**
- URL : https://lightgreen-otter-916987.hostingersite.com/login
- Email : `admin@jobs-sms.com`
- Mot de passe : `password123`

### 5. Debugging

#### Logs Laravel
```bash
tail -f storage/logs/laravel.log
```

#### Logs Apache (si accessible)
```bash
tail -f /var/log/apache2/error.log
```

#### Console navigateur
- F12 > Console : Vérifier les erreurs CORS
- F12 > Network : Vérifier les headers des requêtes API

### 6. Points de vérification

- ✅ CORS activé avec credentials
- ✅ Domaines Hostinger ajoutés aux origines autorisées
- ✅ Sanctum configuré pour les domaines de production
- ✅ APP_URL mis à jour pour HTTPS
- ✅ SESSION_DOMAIN configuré correctement
- ✅ SESSION_SECURE_COOKIE activé pour HTTPS
- ✅ SESSION_SAME_SITE à `none` pour cross-origin

### 7. Rollback (si problème)

En cas de problème, restaurer l'ancien .env :
```bash
cp .env.backup .env
php artisan config:clear
php artisan cache:clear
```

## Explication technique

### Pourquoi ça fonctionnait sur Chrome mais pas ailleurs ?

Chrome a des politiques CORS plus permissives en mode développement. Les autres navigateurs (Firefox, Safari, Edge) appliquent strictement les règles CORS, notamment :

1. **Credentials** : Bloquent les requêtes avec tokens si `supports_credentials` est `false`
2. **Origins** : Vérifient strictement que le domaine est dans la liste `allowed_origins`
3. **Preflight** : Envoient des requêtes OPTIONS pour vérifier les permissions

### Les changements clés

1. **`supports_credentials: true`** : Permet l'envoi de tokens Bearer dans les headers
2. **`CORS_ALLOWED_ORIGINS`** : Liste explicite des domaines autorisés (plus sécurisé que `*`)
3. **`SANCTUM_STATEFUL_DOMAINS`** : Indique à Sanctum les domaines de confiance
4. **`SESSION_SAME_SITE: none`** : Permet les cookies cross-origin (nécessaire avec HTTPS)
5. **`SESSION_SECURE_COOKIE: true`** : Force HTTPS pour les cookies (sécurité)

## Support

En cas de problème persistant :
1. Vérifier les logs Laravel
2. Vérifier la console navigateur
3. Tester l'API directement avec Postman/Insomnia
4. Vérifier que le serveur Apache a `mod_headers` activé
