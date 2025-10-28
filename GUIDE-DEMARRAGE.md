# Guide de Démarrage Rapide - JOBS SMS

## Pour avoir une solution 100% fonctionnelle

### Étapes à suivre:

## 1. ✅ Backend Laravel (DÉJÀ FAIT)

Les éléments suivants sont déjà configurés:
- ✅ Base de données et migrations
- ✅ Modèles Eloquent
- ✅ Authentification Laravel Sanctum
- ✅ Contrôleurs API complets (CRUD)
- ✅ Services SMS (MSG91, SMSALA, WAPI)
- ✅ Routes API REST
- ✅ Seeder utilisateur de test

## 2. 📝 Frontend React (À FINALISER)

### Remplacer le store Zustand

**Fichier à modifier:** `resources/src/lib/store.ts`

Remplacez le contenu par celui de `resources/src/lib/store-new.ts`:

```bash
mv resources/src/lib/store.ts resources/src/lib/store-old.ts
mv resources/src/lib/store-new.ts resources/src/lib/store.ts
```

### Modifier les pages pour charger les données

Les pages doivent appeler les méthodes `load*()` au montage. Exemple pour `Dashboard.tsx`:

```tsx
import { useEffect } from 'react';
import { useAppStore } from "@/lib/store";

export default function Dashboard() {
  const { campaigns, loadCampaigns } = useAppStore();

  useEffect(() => {
    loadCampaigns(); // Charger les données depuis l'API
  }, [loadCampaigns]);

  // ... reste du code
}
```

Appliquez le même pattern pour:
- `Contacts.tsx` → `loadContacts()`
- `Templates.tsx` → `loadTemplates()`
- `Accounts.tsx` → `loadSubAccounts()` et `loadApiKeys()`

### Adapter les noms de propriétés

L'API Laravel utilise `snake_case` alors que le frontend utilise `camelCase`.

**Changements dans les composants:**

| Ancien (Frontend) | Nouveau (API) |
|------------------|---------------|
| `lastConnection` | `last_connection` |
| `messagesSent` | `messages_sent` |
| `deliveryRate` | `delivery_rate` |
| `ctr` | `ctr` (inchangé) |
| `createdAt` | `created_at` |
| `lastModified` | `updated_at` |

**Exemple dans Dashboard.tsx:**

```tsx
// AVANT
{
  key: 'messagesSent',
  header: 'Messages Envoyés',
}

// APRÈS
{
  key: 'messages_sent',
  header: 'Messages Envoyés',
}
```

## 3. 🔧 Configuration

### Vérifier le fichier .env

```bash
# APP
APP_URL=http://localhost:8000

# Base de données (déjà configuré en SQLite)
DB_CONNECTION=sqlite

# SMS Services (ajoutez vos clés API)
MSG91_API_KEY=votre_cle_msg91
MSG91_SENDER_ID=JOBSMS

SMSALA_API_KEY=votre_cle_smsala
SMSALA_SENDER_ID=JOBSMS

WAPI_API_KEY=votre_cle_wapi
WAPI_SENDER_ID=JOBSMS
```

## 4. 🚀 Démarrer l'application

### Terminal 1 - Backend Laravel
```bash
cd /Applications/MAMP/htdocs/sendwave-pro
php artisan serve
```
→ Backend sur `http://localhost:8000`

### Terminal 2 - Frontend Vite
```bash
cd /Applications/MAMP/htdocs/sendwave-pro
npm run dev
```
→ Vite dev server sur `http://localhost:8080`

### Accès à l'application
Ouvrir le navigateur sur **`http://localhost:8000`**

## 5. 🧪 Tester la solution

### Connexion
- Email: `admin@jobs-sms.com`
- Mot de passe: `password123`

### Tester l'API manuellement (Postman/Insomnia)

**1. Login**
```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "admin@jobs-sms.com",
  "password": "password123"
}
```

Réponse attendue:
```json
{
  "access_token": "...",
  "token_type": "Bearer",
  "user": {...}
}
```

**2. Récupérer les campagnes**
```http
GET http://localhost:8000/api/campaigns
Authorization: Bearer {votre_token}
```

**3. Créer un contact**
```http
POST http://localhost:8000/api/contacts
Authorization: Bearer {votre_token}
Content-Type: application/json

{
  "name": "Test Contact",
  "phone": "+241 66 12 34 56",
  "group": "Clients",
  "status": "Actif"
}
```

## 6. 📊 Vérifier le fonctionnement

### Checklist fonctionnelle:

- [ ] ✅ Connexion au frontend
- [ ] ✅ Affichage du dashboard avec métriques
- [ ] ✅ Liste des contacts depuis l'API
- [ ] ✅ Création d'un nouveau contact
- [ ] ✅ Modification d'un contact
- [ ] ✅ Suppression d'un contact
- [ ] ✅ Même chose pour campagnes
- [ ] ✅ Même chose pour templates
- [ ] ✅ Déconnexion

## 7. 🐛 Debugging

### Si le frontend ne se connecte pas:

1. **Vérifier la console du navigateur** (F12) pour voir les erreurs
2. **Vérifier les logs Laravel** : `storage/logs/laravel.log`
3. **Vérifier que CORS est activé** : `config/cors.php`
4. **Vérifier le token** : `localStorage.getItem('auth_token')` dans la console

### Si les données ne s'affichent pas:

1. **Vérifier les appels API** dans l'onglet Network du navigateur
2. **Vérifier que le token est envoyé** dans les headers
3. **Vérifier que l'utilisateur a des données** : Se connecter et créer quelques entrées

### Erreur CORS:

Si vous voyez des erreurs CORS, vérifier `config/cors.php`:
```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'], // En dev uniquement
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

## 8. 🎯 Production

Pour le déploiement en production:

### Backend
```bash
# Build frontend
npm run build

# Optimiser Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Mettre en place les variables d'environnement
APP_ENV=production
APP_DEBUG=false
```

### Variables d'environnement production

```env
APP_URL=https://votre-domaine.com
DB_CONNECTION=mysql
DB_DATABASE=jobs_sms_prod
```

## 📚 Ressources

- **Documentation API**: Voir `README.md` pour tous les endpoints
- **Services SMS**: Voir `app/Services/SMS/` pour l'utilisation
- **Frontend**: Voir `resources/src/` pour les composants React

## 🔑 Points importants

1. **Sécurité**: Les tokens Sanctum expirent. Implémenter une logique de refresh si nécessaire
2. **Pagination**: Pour de grandes listes, ajoutez la pagination aux contrôleurs
3. **Validation**: Tous les contrôleurs ont une validation des données entrantes
4. **Erreurs**: Les erreurs API sont gérées et loggées

## 💡 Prochaines améliorations suggérées

- [ ] Ajouter la pagination sur les listes
- [ ] Implémenter l'upload de fichiers CSV pour les contacts
- [ ] Ajouter des graphiques pour les statistiques
- [ ] Implémenter l'envoi réel de SMS avec les providers
- [ ] Ajouter des tests automatisés
- [ ] Mettre en place un système de queues pour les envois massifs

---

**Support**: Pour toute question, consultez les fichiers README.md et la documentation Laravel.
