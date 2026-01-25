# CLAUDE.md - SendWave Pro Context File

> Ce fichier contient toutes les informations necessaires pour comprendre et travailler sur le projet SendWave Pro.

## 1. Vue d'ensemble

**SendWave Pro** (rebrand: **JOBS SMS**) est une plateforme de gestion de campagnes SMS pour le marche gabonais.

| Info | Valeur |
|------|--------|
| Version | 3.1 (Production Ready) |
| Marche cible | Gabon (Airtel, Moov) |
| Devise | FCFA |
| Langue par defaut | Francais |
| Timezone | Africa/Libreville |
| URL Production | http://161.35.159.160 |

## 2. Stack technique

### Backend
- **PHP 8.2+** avec **Laravel 11**
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Laravel Sanctum** (authentification API)
- **Laravel Queue** (jobs en arriere-plan)
- **Maatwebsite/Excel** (exports Excel)
- **BarryVDH/Laravel-DomPDF** (exports PDF)
- **SMPP Client natif** (pour Moov Gabon)

### Frontend
- **Vue 3** (Composition API)
- **TypeScript**
- **Vite** (build tool)
- **Tailwind CSS**
- **Pinia** (state management)
- **Vue Router 4**
- **Axios** (HTTP client)
- **Heroicons** (icones)

## 3. Structure du projet

```
sendwave-pro/
├── app/
│   ├── Http/Controllers/          # Controleurs
│   │   ├── Api/                   # 14 controleurs API
│   │   ├── MessageController.php
│   │   ├── BudgetController.php
│   │   └── SmsAnalyticsController.php
│   ├── Models/                    # 19 modeles Eloquent
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
│   │   ├── SmsProvider.php
│   │   ├── Blacklist.php
│   │   ├── AuditLog.php
│   │   ├── Webhook.php
│   │   ├── WebhookLog.php
│   │   ├── DailyAnalytic.php
│   │   ├── SmsAnalytics.php
│   │   └── PeriodClosure.php
│   ├── Services/                  # 16 services
│   │   ├── AnalyticsService.php
│   │   ├── AnalyticsRecordService.php
│   │   ├── BudgetService.php
│   │   ├── MessageVariableService.php
│   │   ├── PeriodClosureService.php
│   │   ├── PhoneNormalizationService.php
│   │   ├── StopWordService.php
│   │   ├── WebhookService.php
│   │   └── SMS/
│   │       ├── SmsRouter.php            # Routage intelligent + fallback
│   │       ├── OperatorDetector.php     # Detection operateur
│   │       ├── SmppClient.php           # Client SMPP natif
│   │       └── Operators/
│   │           ├── AirtelService.php
│   │           └── MoovService.php
│   ├── Enums/                     # 5 enums
│   │   ├── CampaignStatus.php
│   │   ├── MessageStatus.php
│   │   ├── SubAccountRole.php
│   │   ├── SubAccountPermission.php
│   │   └── WebhookEvent.php
│   ├── Events/                    # Evenements budget
│   │   ├── BudgetAlertEvent.php
│   │   └── BudgetExceededEvent.php
│   ├── Jobs/                      # 4 jobs
│   │   ├── SendSmsJob.php
│   │   ├── TriggerWebhookJob.php
│   │   ├── SendScheduledReportJob.php
│   │   └── UpdateDailyAnalytics.php
│   └── Console/Commands/
│       └── ClosePeriodCommand.php
├── database/
│   └── migrations/                # 37 migrations
├── routes/
│   ├── api.php                    # 102+ endpoints API
│   ├── web.php
│   └── console.php                # Scheduler
├── resources/
│   └── src/                       # Frontend Vue 3
│       ├── views/                 # 24 pages
│       ├── components/            # 50+ composants UI
│       ├── services/              # 10 services API
│       ├── stores/                # Pinia stores
│       └── router/
├── config/
│   └── sms.php                    # Config SMS (fallback, couts)
├── tests/
│   ├── Unit/Services/             # Tests unitaires
│   └── Feature/Services/          # Tests integration
├── CLAUDE.md                      # Ce fichier
└── README.md
```

## 4. Base de donnees (20+ tables)

### Tables principales
| Table | Description |
|-------|-------------|
| `users` | Comptes utilisateurs principaux |
| `sub_accounts` | Sous-comptes avec roles et budgets |
| `contacts` | Contacts avec champs personnalises (JSON) |
| `contact_groups` | Groupes de contacts |
| `contact_group_members` | Pivot contacts-groupes |
| `campaigns` | Campagnes SMS |
| `campaign_schedules` | Planification recurrente |
| `campaign_variants` | Variantes A/B testing |
| `messages` | Historique des messages |
| `message_templates` | Modeles de messages |
| `blacklist` | Numeros bloques (avec source) |
| `audit_logs` | Logs d'audit |
| `webhooks` | Configuration webhooks |
| `webhook_logs` | Logs de livraison webhooks |
| `sms_configs` | Configuration operateurs SMS |
| `sms_providers` | Definitions des providers |
| `api_keys` | Cles API (avec sub_account_id) |
| `daily_analytics` | Statistiques quotidiennes |
| `sms_analytics` | Analytique detaillee par SMS |
| `period_closures` | Clotures mensuelles |

## 5. Operateurs SMS (Gabon)

### Airtel Gabon
- **Prefixes**: 74, 76, 77
- **API**: HTTP REST (avec SSL bypass car certificat auto-signe)
- **Service**: `app/Services/SMS/Operators/AirtelService.php`
- **Format numero**: Le systeme ajoute automatiquement le prefixe `241`
- **Config env**: `AIRTEL_API_URL`, `AIRTEL_USERNAME`, `AIRTEL_PASSWORD`, `AIRTEL_ORIGIN_ADDR`

### Moov Gabon
- **Prefixes**: 60, 62, 65, 66
- **Protocole**: SMPP v3.4
- **Service**: `app/Services/SMS/Operators/MoovService.php`
- **Client SMPP**: `app/Services/SMS/SmppClient.php` (client natif PHP)
- **Note**: Necessite VPN pour acceder au serveur SMPP (IP privee)
- **Config env**: `MOOV_SMPP_HOST`, `MOOV_SMPP_PORT`, `MOOV_SMPP_SYSTEM_ID`, `MOOV_SMPP_PASSWORD`

### Routage automatique avec Fallback
```php
// Detection operateur
$operator = OperatorDetector::detect($phone); // 'airtel', 'moov', 'unknown'

// Envoi avec fallback automatique
$result = $smsRouter->sendSms($phone, $message, allowFallback: true);
// Si Airtel echoue -> tente Moov automatiquement (et vice versa)
```

**Codes d'erreur recoverables** (declenchent fallback):
- `CONNECTION_ERROR`, `TIMEOUT`, `SERVICE_UNAVAILABLE`
- `GATEWAY_ERROR`, `RATE_LIMIT`, `OPERATOR_DISABLED`

### Configuration dynamique
Priorite: **Database (SmsConfig)** > **Fichier .env**

## 6. Fonctionnalites Phase 3

### 6.1 Fallback Automatique
- Active par defaut (`SMS_FALLBACK_ENABLED=true`)
- Airtel -> Moov si erreur recuperable
- Moov -> Airtel si erreur recuperable
- Logs detailles des tentatives

### 6.2 Gestion STOP Automatique
**Service**: `StopWordService.php`

Mots-cles detectes (16):
```
STOP, ARRET, ARRÊT, UNSUB, UNSUBSCRIBE, DESABONNER, DÉSABONNER,
DESINSCRIPTION, DÉSINSCRIPTION, ANNULER, REMOVE, QUIT, END,
CANCEL, OPTOUT, OPT-OUT
```

- Detection avec normalisation accents
- Ajout automatique a la blacklist (source: `auto_stop`)
- Filtrage avant envoi dans `MessageController`

### 6.3 Normalisation E.164
**Service**: `PhoneNormalizationService.php`

Pays supportes:
| Code | Pays | Prefixe | Longueur locale |
|------|------|---------|-----------------|
| GA | Gabon | 241 | 8 |
| CM | Cameroun | 237 | 9 |
| CG | Congo | 242 | 9 |
| CI | Cote d'Ivoire | 225 | 10 |
| SN | Senegal | 221 | 9 |

```php
$service->normalize('77123456'); // +24177123456
$service->normalize('+237650123456'); // Detection auto CM
$service->normalizeMany($phones); // Groupement par pays/operateur
```

## 7. API Endpoints (102+)

### Authentication
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
GET    /api/user/profile
PUT    /api/user/profile
```

### Messages
```
POST   /api/messages/send           # Rate limited, filtre blacklist
POST   /api/messages/analyze
POST   /api/messages/number-info
GET    /api/messages/history
GET    /api/messages/export
GET    /api/messages/stats
```

### Contacts & Groupes
```
# Contacts
GET|POST      /api/contacts
GET|PUT|DEL   /api/contacts/{id}
POST          /api/contacts/import
GET           /api/contacts/export

# Groupes
GET|POST      /api/contact-groups
GET|PUT|DEL   /api/contact-groups/{id}
GET           /api/contact-groups/{id}/contacts
POST          /api/contact-groups/{id}/contacts/add
POST          /api/contact-groups/{id}/contacts/remove
```

### Campagnes
```
GET|POST      /api/campaigns
GET|PUT|DEL   /api/campaigns/{id}
POST          /api/campaigns/{id}/send
POST          /api/campaigns/{id}/schedule
GET|DEL       /api/campaigns/{id}/schedule
POST          /api/campaigns/{id}/variants
GET|DEL       /api/campaigns/{id}/variants
GET           /api/campaigns/history
GET           /api/campaigns/stats
```

### SMS Analytics & Budgets
```
GET    /api/sms-analytics
GET    /api/sms-analytics/overview
GET    /api/sms-analytics/periods
GET    /api/sms-analytics/closures
GET    /api/sms-analytics/closures/{periodKey}
POST   /api/sms-analytics/report
GET    /api/sms-analytics/export

GET    /api/budgets/status/{subAccountId?}
GET    /api/budgets/all
PUT    /api/budgets/{subAccountId}
POST   /api/budgets/check-send
GET    /api/budgets/history/{subAccountId?}
```

### Blacklist & STOP
```
GET    /api/blacklist
POST   /api/blacklist
DELETE /api/blacklist/{id}
POST   /api/blacklist/check
GET    /api/blacklist/stats
GET    /api/blacklist/stop-keywords
```

### Phone Normalization
```
GET    /api/phone/countries          # Public
POST   /api/phone/normalize
POST   /api/phone/normalize-many
```

### Incoming SMS Webhooks (Public)
```
POST   /api/webhooks/incoming/sms      # Generique
POST   /api/webhooks/incoming/airtel   # Format Airtel
POST   /api/webhooks/incoming/moov     # Format Moov
```

### Webhooks (Configuration)
```
GET|POST      /api/webhooks
GET|PUT|DEL   /api/webhooks/{id}
GET           /api/webhooks/events
GET           /api/webhooks/{id}/logs
GET           /api/webhooks/{id}/stats
POST          /api/webhooks/{id}/test
POST          /api/webhooks/{id}/toggle
```

### Analytics & Exports
```
GET    /api/analytics/dashboard
GET    /api/analytics/chart
GET    /api/analytics/report
GET    /api/analytics/providers
GET    /api/analytics/top-campaigns
POST   /api/analytics/update
GET    /api/analytics/export/pdf
GET    /api/analytics/export/excel
GET    /api/analytics/export/csv
```

### Configuration
```
# SMS Config
GET    /api/sms-configs
GET    /api/sms-configs/{provider}
PUT    /api/sms-configs/{provider}
POST   /api/sms-configs/{provider}/test
POST   /api/sms-configs/{provider}/toggle
POST   /api/sms-configs/{provider}/reset

# API Keys
GET|POST      /api/api-keys
GET|PUT|DEL   /api/api-keys/{id}
POST          /api/api-keys/{id}/revoke
POST          /api/api-keys/{id}/regenerate

# Sous-comptes
GET|POST      /api/sub-accounts
GET|PUT|DEL   /api/sub-accounts/{id}
POST          /api/sub-accounts/login    # Public
POST          /api/sub-accounts/{id}/credits
POST          /api/sub-accounts/{id}/permissions
POST          /api/sub-accounts/{id}/suspend
POST          /api/sub-accounts/{id}/activate
```

## 8. Roles et permissions

### Roles sous-comptes
| Role | Permissions par defaut |
|------|------------------------|
| `admin` | Toutes |
| `manager` | send_sms, view_history, manage_contacts, manage_groups, create_campaigns, view_analytics |
| `sender` | send_sms, view_history |
| `viewer` | view_history |

### Permissions disponibles (8)
```php
SEND_SMS, VIEW_HISTORY, MANAGE_CONTACTS, MANAGE_GROUPS,
CREATE_CAMPAIGNS, VIEW_ANALYTICS, MANAGE_TEMPLATES, EXPORT_DATA
```

## 9. Webhooks (14 evenements)

```
message.sent
message.delivered
message.failed
message.received          # Nouveau (reponse SMS)
campaign.started
campaign.completed
campaign.failed
contact.created
contact.updated
contact.deleted
contact.unsubscribed      # Nouveau (STOP recu)
sub_account.created
sub_account.suspended
blacklist.added
```

Securite: Signature **HMAC-SHA256** dans header `X-Webhook-Signature`

## 10. Variables de messages

```
{nom}           -> Contact.last_name
{prenom}        -> Contact.first_name
{email}         -> Contact.email
{telephone}     -> Contact.phone
{custom.field}  -> Contact.custom_fields.field
```

## 11. Routes Vue.js (SPA)

| Route | Page | Description |
|-------|------|-------------|
| `/dashboard` | Dashboard.vue | Tableau de bord |
| `/send-sms` | SendSms.vue | Envoi SMS (3 onglets) |
| `/transactional` | Transactional.vue | Sender ID, Templates, Drafts, Routes |
| `/contacts` | Contacts.vue | Gestion contacts |
| `/database` | Database.vue | Groupes, Import, Export |
| `/contact-groups` | ContactGroups.vue | Groupes de contacts |
| `/templates` | Templates.vue | Modeles de messages |
| `/campaign/create` | CampaignCreate.vue | Creation campagne |
| `/campaigns/history` | CampaignHistory.vue | Historique campagnes |
| `/messages/history` | MessageHistory.vue | Historique messages |
| `/calendar` | Calendar.vue | Calendrier |
| `/reports` | Reports.vue | Rapports (5 onglets) |
| `/accounts` | Accounts.vue | Sous-comptes |
| `/sms-config` | SmsConfig.vue | Config operateurs SMS |
| `/api-keys` | ApiIntegrations.vue | Cles API clients |
| `/webhooks` | Webhooks.vue | Configuration webhooks |
| `/blacklist` | Blacklist.vue | Liste noire |
| `/audit-logs` | AuditLogs.vue | Journal d'audit |
| `/profile` | Profile.vue | Profil utilisateur |
| `/settings` | Settings.vue | Parametres |

## 12. Commandes utiles

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

# Tests
php artisan test                              # Tous les tests
php artisan test tests/Unit/Services/         # Tests unitaires services
php artisan test --group=integration          # Tests integration (MySQL requis)
```

### Production
```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Commandes specifiques
```bash
# Cloture mensuelle manuelle
php artisan sms:close-period --period=2026-01

# Recalculer analytics
php artisan analytics:update --days=30

# Vider caches
php artisan optimize:clear
```

### Scheduler (routes/console.php)
```php
// Campagnes planifiees - chaque minute
Schedule::command('campaigns:process-scheduled')->everyMinute();

// Analytics quotidiennes - 00:05
Schedule::job(new UpdateDailyAnalytics())->dailyAt('00:05');

// Rapports hebdomadaires - Lundi 08:00
Schedule::job(new SendScheduledReportJob('weekly'))->weeklyOn(1, '08:00');

// Rapports mensuels - 1er du mois 08:00
Schedule::job(new SendScheduledReportJob('monthly'))->monthlyOn(1, '08:00');

// Cloture mensuelle - 1er du mois 00:30
Schedule::command('sms:close-period')->monthlyOn(1, '00:30')->timezone('Africa/Libreville');
```

### Cron
```bash
* * * * * cd /path/to/sendwave-pro && php artisan schedule:run >> /dev/null 2>&1
```

## 13. Variables d'environnement cles

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
AIRTEL_COST_PER_SMS=20

# Moov Gabon (SMPP)
MOOV_SMPP_HOST=172.16.59.66
MOOV_SMPP_PORT=12775
MOOV_SMPP_SYSTEM_ID=
MOOV_SMPP_PASSWORD=
MOOV_SOURCE_ADDR=SENDWAVE
MOOV_ENABLED=false
MOOV_COST_PER_SMS=20

# SMS Global
SMS_COST_PER_UNIT=20
SMS_FALLBACK_ENABLED=true

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:8888,yourdomain.com
```

## 14. Points d'attention

1. **Rate limiting**: Endpoint `/api/messages/send` limite (throttle:sms-send)
2. **Filtrage blacklist**: Automatique avant chaque envoi
3. **Fallback SMS**: Active par defaut, desactivable par config
4. **STOP detection**: Fonctionne avec/sans accents
5. **Scope utilisateur**: Toutes les requetes scopees par user authentifie
6. **Webhooks**: Signature HMAC-SHA256 obligatoire
7. **SSL Airtel**: Certificat auto-signe, `withoutVerifying()` utilise
8. **Format numeros**: Prefixe `241` ajoute automatiquement pour Gabon
9. **Cache Analytics**: 5 minutes dashboard, invalide apres envoi
10. **Cloture mensuelle**: Automatique le 1er du mois a 00:30

## 15. Statistiques du projet

| Metrique | Valeur |
|----------|--------|
| Tables DB | 20+ |
| Modeles Eloquent | 19 |
| Controleurs API | 14 |
| Routes API | 102+ |
| Services | 16 |
| Pages Vue | 24 |
| Composants UI | 50+ |
| Migrations | 37 |
| Enums | 5 |
| Jobs Background | 4 |
| Evenements Webhook | 14 |
| Tests | 46 |

## 16. Plan d'Action - Status

### PLAN_ACTION_V2.md - COMPLETE

| Phase | Description | Status |
|-------|-------------|--------|
| **Phase 1** | Comptabilite Analytique | ✅ 100% |
| **Phase 2** | Refonte Interfaces | ✅ 100% |
| **Phase 3** | Fonctionnalites Avancees | ✅ 100% |
| **Ameliorations** | Tests, Scheduler, Migrations | ✅ 100% |

**Phase 1 - Comptabilite Analytique**
- ✅ Table `sms_analytics` - Tracabilite complete par SMS
- ✅ Table `period_closures` - Cloture mensuelle automatique
- ✅ `BudgetService` - Plafonds mensuels par sous-compte
- ✅ API Keys rattachees aux sous-comptes
- ✅ Commande `sms:close-period`
- ✅ Scheduler cloture automatique

**Phase 2 - Refonte Interfaces**
- ✅ `SendSms.vue` avec 3 onglets (Send SMS | Send Opt SMS | SMS From File)
- ✅ `Transactional.vue` avec 4 onglets (Sender Id | Templates | Drafts | Routes)
- ✅ `Database.vue` avec 3 onglets (My Groups | Import | Export)
- ✅ `Reports.vue` avec 5 onglets (Campaign | Delivery | Schedule | Archived | Credit)
- ✅ `TabNav.vue` composant reutilisable

**Phase 3 - Fonctionnalites Avancees**
- ✅ Fallback automatique Airtel <-> Moov
- ✅ `StopWordService` - Gestion STOP automatique
- ✅ `PhoneNormalizationService` - Support E.164 international
- ✅ Webhooks incoming SMS
- ✅ Nouveaux evenements webhook (message.received, contact.unsubscribed)

**Tests Unitaires**
- ✅ `StopWordServiceTest` (13 tests)
- ✅ `PhoneNormalizationServiceTest` (22 tests)
- ✅ `BudgetServiceTest` (11 tests - groupe integration)
- ✅ `OperatorDetectorTest` (existant)

---

*Derniere mise a jour: 25 Janvier 2026*
