# JOBS SMS - API Laravel + React

Application complète de gestion de campagnes SMS avec backend Laravel et frontend React intégré.

## Technologies

### Backend
- **Laravel 12** - Framework PHP
- **Laravel Sanctum** - Authentification API
- **MySQL/SQLite** - Base de données

### Frontend
- **React 18** - Framework JavaScript
- **TypeScript** - Typage statique
- **Vite** - Build tool
- **Tailwind CSS** - Framework CSS
- **shadcn-ui** - Composants UI
- **Zustand** - State management
- **React Router** - Routing

### Services SMS
- **MSG91** - Provider SMS international
- **SMSALA** - Provider SMS Afrique
- **WAPI** - Provider WhatsApp/SMS Afrique

## Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js 18+
- NPM/Bun
- MySQL/SQLite

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone <url-du-repo>
cd sendwave-pro
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances JavaScript**
```bash
npm install
```

4. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurer la base de données**

Éditer `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jobs_sms
DB_USERNAME=root
DB_PASSWORD=
```

Ou utiliser SQLite (par défaut):
```env
DB_CONNECTION=sqlite
```

6. **Configurer les API SMS**

Éditer `.env` :
```env
# MSG91
MSG91_API_KEY=votre_cle_api
MSG91_SENDER_ID=JOBSMS

# SMSALA
SMSALA_API_KEY=votre_cle_api
SMSALA_SENDER_ID=JOBSMS

# WAPI
WAPI_API_KEY=votre_cle_api
WAPI_SENDER_ID=JOBSMS
```

7. **Exécuter les migrations**
```bash
php artisan migrate
```

8. **Seeder (données de test)**
```bash
php artisan db:seed --class=UserSeeder
```

Utilisateur créé:
- Email: `admin@jobs-sms.com`
- Mot de passe: `password123`

## Développement

### Lancer le serveur Laravel
```bash
php artisan serve
```
Le serveur démarre sur `http://localhost:8000`

### Lancer Vite (pour le frontend React)
```bash
npm run dev
```

### Build de production
```bash
npm run build
```

## Structure du projet

```
sendwave-pro/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php
│   │           ├── ContactController.php
│   │           ├── CampaignController.php
│   │           ├── MessageTemplateController.php
│   │           ├── SubAccountController.php
│   │           └── ApiKeyController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Contact.php
│   │   ├── Campaign.php
│   │   ├── MessageTemplate.php
│   │   ├── SubAccount.php
│   │   └── ApiKey.php
│   └── Services/
│       └── SMS/
│           ├── SmsServiceInterface.php
│           ├── SmsServiceFactory.php
│           ├── Msg91Service.php
│           ├── SmsAlaService.php
│           └── WapiService.php
├── resources/
│   ├── src/              # Code source React
│   │   ├── components/
│   │   ├── pages/
│   │   ├── lib/
│   │   └── main.tsx
│   └── views/
│       └── app.blade.php # Point d'entrée React
├── database/
│   ├── migrations/
│   └── seeders/
└── routes/
    ├── api.php           # Routes API
    └── web.php           # Routes web (frontend React)
```

## API Endpoints

### Authentification

#### POST `/api/auth/login`
Connexion utilisateur
```json
{
  "email": "admin@jobs-sms.com",
  "password": "password123"
}
```

#### POST `/api/auth/register`
Inscription utilisateur
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "User"
}
```

#### POST `/api/auth/logout`
Déconnexion (requiert authentification)

#### GET `/api/auth/me`
Obtenir l'utilisateur connecté (requiert authentification)

### Ressources (requièrent authentification)

Toutes les routes suivantes nécessitent le header:
```
Authorization: Bearer {token}
```

#### Contacts
- `GET /api/contacts` - Liste des contacts
- `POST /api/contacts` - Créer un contact
- `GET /api/contacts/{id}` - Voir un contact
- `PUT /api/contacts/{id}` - Modifier un contact
- `DELETE /api/contacts/{id}` - Supprimer un contact

#### Campagnes
- `GET /api/campaigns` - Liste des campagnes
- `POST /api/campaigns` - Créer une campagne
- `GET /api/campaigns/{id}` - Voir une campagne
- `PUT /api/campaigns/{id}` - Modifier une campagne
- `DELETE /api/campaigns/{id}` - Supprimer une campagne

#### Templates de message
- `GET /api/templates` - Liste des templates
- `POST /api/templates` - Créer un template
- `GET /api/templates/{id}` - Voir un template
- `PUT /api/templates/{id}` - Modifier un template
- `DELETE /api/templates/{id}` - Supprimer un template

#### Sous-comptes
- `GET /api/sub-accounts` - Liste des sous-comptes
- `POST /api/sub-accounts` - Créer un sous-compte
- `GET /api/sub-accounts/{id}` - Voir un sous-compte
- `PUT /api/sub-accounts/{id}` - Modifier un sous-compte
- `DELETE /api/sub-accounts/{id}` - Supprimer un sous-compte

#### Clés API
- `GET /api/api-keys` - Liste des clés API
- `POST /api/api-keys` - Créer une clé API
- `GET /api/api-keys/{id}` - Voir une clé API
- `PUT /api/api-keys/{id}` - Modifier une clé API
- `DELETE /api/api-keys/{id}` - Supprimer une clé API

## Utilisation des services SMS

### Dans le code PHP

```php
use App\Services\SMS\SmsServiceFactory;

// Créer un service selon le provider
$smsService = SmsServiceFactory::make('msg91'); // ou 'smsala', 'wapi'

// Envoyer un SMS
$result = $smsService->sendSms('+241066123456', 'Votre message ici');

// Envoyer un SMS en masse
$recipients = ['+241066123456', '+241077654321'];
$result = $smsService->sendBulkSms($recipients, 'Votre message ici');

// Vérifier le statut d'un message
$status = $smsService->getMessageStatus('message_id_123');

// Vérifier le solde
$balance = $smsService->getBalance();
```

## Commandes Artisan utiles

```bash
# Créer un contrôleur API
php artisan make:controller Api/NomController --api

# Créer un modèle avec migration
php artisan make:model NomModele -m

# Créer un seeder
php artisan make:seeder NomSeeder

# Rafraîchir la base de données
php artisan migrate:fresh

# Voir les routes
php artisan route:list
```

## Configuration CORS

Le CORS est configuré par défaut dans Laravel. Pour l'ajuster, éditer `config/cors.php`.

## Sécurité

- Laravel Sanctum gère l'authentification API par tokens
- Les mots de passe sont hashés avec bcrypt
- Protection CSRF activée
- Validation des données entrantes
- Rate limiting sur les API

## Production

1. **Build du frontend**
```bash
npm run build
```

2. **Optimiser Laravel**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. **Variables d'environnement**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com
```

## Support

Pour toute question ou problème, contactez l'équipe de développement.

## License

Propriétaire - JOBS SMS © 2024
