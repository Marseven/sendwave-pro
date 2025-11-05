# Configuration Base de Données Production

## Problème
L'authentification échoue avec "Les identifiants fournis sont incorrects" car l'utilisateur de test n'existe pas dans la base de données de production.

## Solution : Exécuter les migrations et seeders sur Hostinger

### Étape 1 : Se connecter en SSH
```bash
ssh u663389624@uk-fast-web1375.main-hosting.eu
cd /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign
```

### Étape 2 : Vérifier la configuration de la base de données
```bash
# Afficher la configuration DB actuelle
grep DB_ .env
```

Vous devriez voir quelque chose comme :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u663389624_sendwave
DB_USERNAME=u663389624_sendwave
DB_PASSWORD=votre_mot_de_passe
```

### Étape 3 : Exécuter les migrations
```bash
# Exécuter toutes les migrations
php artisan migrate --force
```

Si les migrations sont déjà exécutées, vous verrez :
```
Nothing to migrate.
```

### Étape 4 : Créer l'utilisateur de test
```bash
# Exécuter le seeder pour créer l'utilisateur admin
php artisan db:seed --class=UserSeeder --force
```

Cela créera l'utilisateur :
- **Email** : `admin@jobs-sms.com`
- **Mot de passe** : `password123`
- **Rôle** : Admin

### Étape 5 : Vérifier que l'utilisateur a été créé
```bash
# Se connecter à MySQL (si disponible)
mysql -u u663389624_sendwave -p u663389624_sendwave

# Dans MySQL, vérifier les utilisateurs
SELECT id, name, email, role FROM users;
```

Ou via PHP :
```bash
php artisan tinker
>>> \App\Models\User::all();
>>> exit
```

### Option Alternative : Créer l'utilisateur manuellement

Si le seeder ne fonctionne pas, créez l'utilisateur via tinker :

```bash
php artisan tinker
```

Puis exécutez :
```php
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@jobs-sms.com',
    'password' => \Illuminate\Support\Facades\Hash::make('password123'),
    'role' => 'Admin'
]);

exit
```

### Étape 6 : Tester la connexion
Allez sur : https://lightgreen-otter-916987.hostingersite.com/login

Connectez-vous avec :
- Email : `admin@jobs-sms.com`
- Mot de passe : `password123`

## Vérification des erreurs

### Si erreur "Base table or view not found"
Les migrations n'ont pas été exécutées :
```bash
php artisan migrate --force
```

### Si erreur "Connection refused" ou "Access denied"
Problème de configuration MySQL. Vérifiez :
```bash
# Vérifier les credentials
cat .env | grep DB_

# Tester la connexion MySQL
php artisan db:show
```

### Si erreur "Class UserSeeder does not exist"
Le seeder n'existe pas. Créez l'utilisateur manuellement via tinker (voir ci-dessus).

### Vérifier les logs Laravel
```bash
# Voir les dernières erreurs
tail -50 storage/logs/laravel.log
```

## Permissions (si nécessaire)
```bash
chmod -R 755 storage bootstrap/cache
chown -R u663389624:u663389624 storage bootstrap/cache
```

## Rappel : Variables d'environnement importantes

Assurez-vous que votre `.env` contient :
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lightgreen-otter-916987.hostingersite.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u663389624_sendwave
DB_USERNAME=u663389624_sendwave
DB_PASSWORD=votre_mot_de_passe

CORS_ALLOWED_ORIGINS=https://lightgreen-otter-916987.hostingersite.com,http://mysmscampaign.jobs-conseil.com,https://mysmscampaign.jobs-conseil.com
SANCTUM_STATEFUL_DOMAINS=lightgreen-otter-916987.hostingersite.com,mysmscampaign.jobs-conseil.com
```
