# CLAUDE.md - SendWave Pro Context File

> Ce fichier contient toutes les informations necessaires pour comprendre et travailler sur le projet SendWave Pro.

## 1. Vue d'ensemble

**SendWave Pro** est une plateforme de gestion de campagnes SMS pour le marche gabonais.

| Info | Valeur |
|------|--------|
| Version | 3.0 (Production Ready) |
| Marche cible | Gabon (Airtel, Moov) |
| Devise | FCFA |
| Langue par defaut | Francais |
| Timezone | Africa/Libreville |

## 2. Stack technique

### Backend
- **PHP 8.2+** avec **Laravel 12**
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Laravel Sanctum** (authentification API)
- **Laravel Queue** (jobs en arriere-plan)
- **Maatwebsite/Excel** (exports Excel)
- **BarryVDH/Laravel-DomPDF** (exports PDF)

### Frontend
- **Vue 3** (Composition API)
- **TypeScript**
- **Vite** (build tool)
- **Tailwind CSS**
- **Pinia** (state management)
- **Vue Router 4**
- **Axios** (HTTP client)

## 3. Structure du projet

```
sendwave-pro/
├── app/
│   ├── Http/Controllers/Api/     # 14 controleurs API
│   │   ├── AuthController.php
│   │   ├── ContactController.php
│   │   ├── ContactGroupController.php
│   │   ├── CampaignController.php
│   │   ├── MessageController.php
│   │   ├── TemplateController.php
│   │   ├── SubAccountController.php
│   │   ├── ApiKeyController.php
│   │   ├── SmsConfigController.php
│   │   ├── BlacklistController.php
│   │   ├── AuditLogController.php
│   │   ├── WebhookController.php
│   │   └── AnalyticsController.php
│   ├── Models/                    # 18 modeles Eloquent
│   │   ├── User.php
│   │   ├── Contact.php
│   │   ├── ContactGroup.php
│   │   ├── Campaign.php
│   │   ├── CampaignSchedule.php
│   │   ├── CampaignVariant.php
│   │   ├── Message.php
│   │   ├── MessageTemplate.php
│   │   ├── SubAccount.php
│   │   ├── ApiKey.php
│   │   ├── SmsConfig.php
│   │   ├── Blacklist.php
│   │   ├── AuditLog.php
│   │   ├── Webhook.php
│   │   ├── WebhookLog.php
│   │   └── DailyAnalytics.php
│   ├── Services/
│   │   ├── MessageVariableService.php   # Variables dynamiques
│   │   ├── WebhookService.php           # Gestion webhooks
│   │   ├── AnalyticsService.php         # Analytics & rapports
│   │   └── SMS/
│   │       ├── SmsRouter.php            # Routage intelligent
│   │       ├── OperatorDetector.php     # Detection operateur
│   │       └── Operators/
│   │           ├── AirtelGabonProvider.php
│   │           └── MoovGabonProvider.php
│   └── Providers/
├── database/
│   ├── migrations/                # 27 migrations
│   ├── factories/
│   └── seeders/
├── routes/
│   ├── api.php                    # 102+ endpoints API
│   └── web.php
├── resources/
│   └── src/                       # Frontend Vue 3
│       ├── views/                 # 20 composants pages
│       │   ├── Login.vue
│       │   ├── Dashboard.vue
│       │   ├── Contacts.vue
│       │   ├── ContactGroups.vue
│       │   ├── CampaignCreate.vue
│       │   ├── CampaignHistory.vue
│       │   ├── SendMessage.vue
│       │   ├── MessageHistory.vue
│       │   ├── Templates.vue
│       │   ├── Webhooks.vue
│       │   ├── Reports.vue
│       │   ├── Calendar.vue
│       │   ├── Accounts.vue
│       │   ├── ApiConfiguration.vue
│       │   ├── ApiIntegrations.vue
│       │   ├── Profile.vue
│       │   ├── Settings.vue
│       │   └── MainLayout.vue
│       ├── components/            # Composants reutilisables
│       ├── services/              # Services API
│       ├── router/                # Configuration routes
│       ├── stores/                # Pinia stores
│       └── main.ts                # Point d'entree
├── config/                        # Configuration Laravel
├── public/                        # Fichiers publics
├── storage/                       # Logs, cache, uploads
├── docs/                          # Documentation (non commite)
├── CLAUDE.md                      # Ce fichier
└── README.md                      # README principal
```

## 4. Base de donnees (20 tables)

### Tables principales
| Table | Description |
|-------|-------------|
| `users` | Comptes utilisateurs principaux |
| `sub_accounts` | Sous-comptes avec roles |
| `contacts` | Contacts avec champs personnalises (JSON) |
| `contact_groups` | Groupes de contacts |
| `contact_group_members` | Pivot contacts-groupes |
| `campaigns` | Campagnes SMS |
| `campaign_schedules` | Planification recurrente |
| `campaign_variants` | Variantes A/B testing |
| `messages` | Historique des messages |
| `message_templates` | Modeles de messages |
| `blacklist` | Numeros bloques |
| `audit_logs` | Logs d'audit |
| `webhooks` | Configuration webhooks |
| `webhook_logs` | Logs de livraison webhooks |
| `sms_configs` | Configuration operateurs SMS |
| `api_keys` | Cles API |
| `daily_analytics` | Statistiques quotidiennes |

## 5. Operateurs SMS (Gabon)

### Airtel Gabon
- **Prefixes**: 74, 76, 77
- **API**: HTTP REST
- **Config env**: `AIRTEL_API_URL`, `AIRTEL_USERNAME`, `AIRTEL_PASSWORD`

### Moov Gabon
- **Prefixes**: 60, 62, 65, 66
- **API**: HTTP REST
- **Config env**: `MOOV_API_URL`, `MOOV_USERNAME`, `MOOV_PASSWORD`

### Detection automatique
Le `OperatorDetector` detecte l'operateur via le prefixe du numero et le `SmsRouter` route automatiquement vers le bon provider.

## 6. API Endpoints (102+)

### Authentication
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
GET    /api/user/profile
PUT    /api/user/profile
```

### Contacts
```
GET    /api/contacts
POST   /api/contacts
GET    /api/contacts/{id}
PUT    /api/contacts/{id}
DELETE /api/contacts/{id}
POST   /api/contacts/import
```

### Groupes de contacts
```
GET    /api/contact-groups
POST   /api/contact-groups
GET    /api/contact-groups/{id}
PUT    /api/contact-groups/{id}
DELETE /api/contact-groups/{id}
GET    /api/contact-groups/{id}/contacts
POST   /api/contact-groups/{id}/contacts/add
POST   /api/contact-groups/{id}/contacts/remove
```

### Campagnes
```
GET    /api/campaigns
POST   /api/campaigns
GET    /api/campaigns/{id}
PUT    /api/campaigns/{id}
DELETE /api/campaigns/{id}
POST   /api/campaigns/{id}/send
POST   /api/campaigns/{id}/schedule
GET    /api/campaigns/{id}/schedule
DELETE /api/campaigns/{id}/schedule
POST   /api/campaigns/{id}/variants
GET    /api/campaigns/{id}/variants
DELETE /api/campaigns/{id}/variants/{variantId}
```

### Messages
```
POST   /api/messages/send           # Rate limited
POST   /api/messages/analyze
POST   /api/messages/number-info
GET    /api/messages/history
GET    /api/messages/history/export
GET    /api/messages/stats
```

### Templates
```
GET    /api/templates
POST   /api/templates
GET    /api/templates/{id}
PUT    /api/templates/{id}
DELETE /api/templates/{id}
GET    /api/templates/categories
POST   /api/templates/{id}/use
POST   /api/templates/{id}/preview
```

### Sous-comptes
```
GET    /api/sub-accounts
POST   /api/sub-accounts
GET    /api/sub-accounts/{id}
PUT    /api/sub-accounts/{id}
DELETE /api/sub-accounts/{id}
POST   /api/sub-accounts/login      # Public
POST   /api/sub-accounts/{id}/credits
POST   /api/sub-accounts/{id}/permissions
POST   /api/sub-accounts/{id}/suspend
POST   /api/sub-accounts/{id}/activate
```

### Webhooks
```
GET    /api/webhooks
POST   /api/webhooks
GET    /api/webhooks/{id}
PUT    /api/webhooks/{id}
DELETE /api/webhooks/{id}
GET    /api/webhooks/events
GET    /api/webhooks/{id}/logs
GET    /api/webhooks/{id}/stats
POST   /api/webhooks/{id}/test
POST   /api/webhooks/{id}/toggle
```

### Analytics
```
GET    /api/analytics/dashboard
GET    /api/analytics/chart
GET    /api/analytics/report
GET    /api/analytics/export/pdf
GET    /api/analytics/export/excel
GET    /api/analytics/export/csv
GET    /api/analytics/providers
GET    /api/analytics/top-campaigns
POST   /api/analytics/update
```

### Autres
```
# Blacklist
GET    /api/blacklist
POST   /api/blacklist
DELETE /api/blacklist/{id}
POST   /api/blacklist/check

# Audit Logs
GET    /api/audit-logs
GET    /api/audit-logs/actions
GET    /api/audit-logs/{id}

# SMS Config
GET    /api/sms-configs/{provider}
PUT    /api/sms-configs/{provider}
POST   /api/sms-configs/{provider}/test
POST   /api/sms-configs/{provider}/toggle

# API Keys
GET    /api/api-keys
POST   /api/api-keys
DELETE /api/api-keys/{id}
```

## 7. Roles et permissions

### Roles sous-comptes
| Role | Description |
|------|-------------|
| `admin` | Acces complet aux ressources assignees |
| `manager` | Gestion campagnes et contacts |
| `sender` | Envoi SMS uniquement |
| `viewer` | Lecture seule |

## 8. Variables de messages

Le `MessageVariableService` supporte les variables dynamiques:

```
{nom}           -> Contact.last_name
{prenom}        -> Contact.first_name
{email}         -> Contact.email
{telephone}     -> Contact.phone
{custom.field}  -> Contact.custom_fields.field
```

## 9. Webhooks

### Evenements disponibles (12)
```
message.sent
message.delivered
message.failed
campaign.created
campaign.started
campaign.completed
contact.created
contact.updated
contact.deleted
sub_account.created
blacklist.added
blacklist.removed
```

### Signature HMAC-SHA256
Chaque webhook inclut un header `X-Webhook-Signature` pour verification.

## 10. Commandes utiles

### Developpement
```bash
# Installation
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Demarrage
composer dev              # Serveur complet (Laravel + Vite + Queue)
# ou
php artisan serve         # Backend uniquement
npm run dev               # Frontend uniquement

# Tests
php artisan test
```

### Production
```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Cron (campagnes recurrentes)
```bash
* * * * * cd /path/to/sendwave-pro && php artisan schedule:run >> /dev/null 2>&1
```

## 11. Variables d'environnement cles

```env
APP_NAME="SendWave Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Africa/Libreville
APP_LOCALE=fr

# Base de donnees
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=sendwave_pro
DB_USERNAME=user
DB_PASSWORD=password

# Airtel Gabon
AIRTEL_API_URL=https://messaging.airtel.ga:9002/smshttp/qs/
AIRTEL_USERNAME=username
AIRTEL_PASSWORD=password
AIRTEL_ORIGIN_ADDR=SENDWAVE
AIRTEL_ENABLED=true

# Moov Gabon
MOOV_API_URL=
MOOV_USERNAME=
MOOV_PASSWORD=
MOOV_ENABLED=false

# Cout SMS (FCFA)
SMS_COST_PER_UNIT=20
AIRTEL_COST_PER_SMS=20
MOOV_COST_PER_SMS=20

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8888
```

## 12. Conventions de code

### Backend (PHP/Laravel)
- Controllers: `app/Http/Controllers/Api/`
- Models: `app/Models/`
- Services: `app/Services/`
- Routes API: `routes/api.php`
- Validation dans les controllers
- Scope queries par user authentifie

### Frontend (Vue/TypeScript)
- Pages: `resources/src/views/`
- Composants: `resources/src/components/`
- Services API: `resources/src/services/`
- Stores Pinia: `resources/src/stores/`
- Router: `resources/src/router/`

### Git et Commits
- **NE PAS signer les commits au nom de Claude** (pas de `Co-Authored-By: Claude`)
- Messages de commit en francais ou anglais selon le contexte
- Format recommande: `type: description courte`
- Types: `feat`, `fix`, `refactor`, `docs`, `style`, `test`, `chore`
- Exemple: `feat: ajout export PDF des rapports`

## 13. Documentation additionnelle

Les fichiers de documentation detaillee sont dans `/docs/` (non commite):
- `API_DOCUMENTATION.md` - Reference API complete
- `DEPLOYMENT_GUIDE.md` - Guide de deploiement
- `IMPLEMENTATION_SUMMARY.md` - Resume des fonctionnalites
- `DOCUMENTATION-SMS-OPERATORS.md` - Integration operateurs
- `ROADMAP.md` - Feuille de route
- `ROUTES_AUDIT.md` - Audit des routes
- `SWAGGER.md` - Documentation Swagger

## 14. Points d'attention

1. **Rate limiting**: L'endpoint `/api/messages/send` est limite
2. **Authentification**: Sanctum tokens requis pour toutes les routes protegees
3. **Scope utilisateur**: Toutes les requetes sont scopees par l'utilisateur authentifie
4. **Webhooks**: Signature HMAC-SHA256 obligatoire pour la securite
5. **Detection operateur**: Basee sur le prefixe du numero de telephone
6. **Devise**: Tous les montants sont en FCFA

---

*Derniere mise a jour: Janvier 2026*
