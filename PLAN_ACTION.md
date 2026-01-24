# Plan d'Action - SendWave Pro v3.0

> Plan de correction et amelioration - Janvier 2026

## Phase 1: Corrections Critiques (Priorite HAUTE)

### 1.1 Bugs Bloquants

- [x] **1.1.1** Fixer MoovService - Implementer SMPP ✅
  - Fichier: `app/Services/SMS/Operators/MoovService.php`
  - Cree: `app/Services/SMS/SmppClient.php` (client SMPP v3.4 natif PHP)
  - Protocole SMPP sur port 12775, host 172.16.59.66

- [x] **1.1.2** Harmoniser les statuses de messages ✅
  - Cree: `app/Enums/MessageStatus.php` et `app/Enums/CampaignStatus.php`
  - Messages: `pending` -> `sent` -> `delivered` | `failed`
  - Campagnes: `draft` -> `scheduled` -> `sending` -> `completed` | `failed` | `cancelled`
  - Frontend mis a jour pour compatibilite avec anciens statuts

- [x] **1.1.3** Implementer CampaignSchedule::calculateNextRun() ✅
  - Corrige: `app/Models/CampaignSchedule.php` - Methode reecrite avec gestion correcte
  - Cree: `app/Console/Commands/ProcessScheduledCampaigns.php` - Commande pour executer les campagnes
  - Configure: `routes/console.php` - Scheduler toutes les minutes
  - Migration: Ajout `group_id` aux campagnes + mise a jour statuts legacy

- [x] **1.1.4** Fixer contact_id dans Message ✅
  - Ajoute: `findContactByPhone()` dans MessageController, CampaignController, ProcessScheduledCampaigns
  - Recherche flexible: numero exact, nettoye, avec +, ou 8 derniers chiffres
  - Cache des contacts pour optimiser les envois en masse
  - `contact_id` et `recipient_name` maintenant remplis automatiquement

- [x] **1.1.5** Merger les 2 SubAccountControllers ✅
  - Supprime: `app/Http/Controllers/Api/SubAccountController.php` (version simple)
  - Garde: `app/Http/Controllers/SubAccountController.php` (version complete)
  - Routes mises a jour dans `routes/api.php`

### 1.2 Securite Immediate

- [x] **1.2.1** Chiffrer les credentials SMS ✅
  - Fichier: `app/Models/SmsConfig.php`
  - Mutateurs setPasswordAttribute/getPasswordAttribute avec Crypt::encryptString
  - Support rétrocompatible pour mots de passe legacy non-chiffrés
  - Créé: `app/Console/Commands/EncryptSmsPasswords.php` pour migration

- [x] **1.2.2** Ameliorer generation mot de passe sub-accounts ✅
  - Resolu: Le fichier avec `uniqid()` (Api/SubAccountController.php) a ete supprime
  - Le SubAccountController garde utilise Hash::make() avec mot de passe fourni par l'utilisateur

- [x] **1.2.3** Configurer CORS ✅
  - Fichier: `config/cors.php`
  - Méthodes HTTP explicitement listées (pas de wildcard)
  - Headers explicitement listés (Accept, Authorization, Content-Type, X-Requested-With, CSRF)
  - Wildcard '*' uniquement en environnement local quand CORS_ALLOWED_ORIGINS est vide
  - Production requiert CORS_ALLOWED_ORIGINS explicite

---

## Phase 2: Coherence Backend (Priorite MOYENNE)

### 2.1 Nettoyage Code

- [x] **2.1.1** Supprimer code mort et TODO obsoletes ✅
  - Aucun TODO trouvé dans app/ et resources/src/
  - Syntaxe PHP validée sur tous les fichiers
  - Code propre

- [x] **2.1.2** Centraliser les constantes ✅
  - Créé: `app/Enums/MessageStatus.php` ✅
  - Créé: `app/Enums/CampaignStatus.php` ✅
  - Créé: `app/Enums/WebhookEvent.php` ✅
  - Créé: `app/Enums/SubAccountRole.php` ✅
  - Créé: `app/Enums/SubAccountPermission.php` ✅
  - Modèles Webhook et SubAccount mis à jour pour utiliser les enums

- [x] **2.1.3** Refactorer SmsRouter injection ✅
  - MessageController, CampaignController, ProcessScheduledCampaigns
  - Constructor promotion PHP 8 avec injection de SmsRouter et WebhookService

### 2.2 Middleware & Permissions

- [x] **2.2.1** Creer middleware CheckSubAccountPermission ✅
  - Fichier: `app/Http/Middleware/CheckSubAccountPermission.php`
  - Vérifie: statut actif + permission spécifique
  - Alias: `permission` enregistré dans bootstrap/app.php

- [x] **2.2.2** Appliquer middleware aux routes concernees ✅
  - Routes Contacts: permission:manage_contacts
  - Routes Groups: permission:manage_groups
  - Routes Campaigns: permission:create_campaigns
  - Routes Messages: permission:send_sms
  - Routes History: permission:view_history
  - Routes Analytics: permission:view_analytics
  - Routes Templates: permission:manage_templates
  - Routes Export: permission:export_data
  - Routes Admin (sub-accounts, api-keys, webhooks, blacklist, audit-logs, sms-configs): compte parent uniquement

### 2.3 Analytics Automatiques

- [x] **2.3.1** Creer Job UpdateDailyAnalytics ✅
  - Fichier: `app/Jobs/UpdateDailyAnalytics.php`
  - Itère sur tous les utilisateurs et met à jour leurs analytics
  - 3 tentatives avec backoff de 60 secondes

- [x] **2.3.2** Configurer scheduler ✅
  - Fichier: `routes/console.php` (Laravel 11)
  - Exécution quotidienne à 00:05

---

## Phase 3: Interfaces Manquantes (Priorite MOYENNE)

### 3.1 Pages a Creer

- [ ] **3.1.1** Page Blacklist Management
  - Fichier: `resources/src/views/Blacklist.vue`
  - CRUD numeros bloques
  - Import/Export

- [ ] **3.1.2** Page SMS Configuration
  - Fichier: `resources/src/views/SmsConfig.vue`
  - Config Airtel/Moov
  - Test de connexion

- [ ] **3.1.3** Page API Keys Management
  - Fichier: `resources/src/views/ApiKeys.vue`
  - Generer/Revoquer cles API
  - Afficher usage

- [ ] **3.1.4** Page Audit Logs Viewer
  - Fichier: `resources/src/views/AuditLogs.vue`
  - Filtres par action/user/date
  - Export logs

### 3.2 Features a Completer

- [ ] **3.2.1** UI Planification Campagnes
  - Fichier: `resources/src/views/CampaignCreate.vue`
  - Ajouter date picker pour scheduling
  - Frequence recurrence

- [ ] **3.2.2** UI A/B Testing
  - Fichier: `resources/src/views/CampaignCreate.vue`
  - Ajouter variantes de messages
  - Afficher resultats dans historique

- [ ] **3.2.3** UI Variables Templates
  - Fichier: `resources/src/views/Templates.vue`
  - Liste variables disponibles
  - Preview avec remplacement

- [ ] **3.2.4** UI Export Analytics
  - Fichier: `resources/src/views/Reports.vue`
  - Boutons export PDF/Excel
  - Selection periode

- [ ] **3.2.5** Completer page Settings
  - Fichier: `resources/src/views/Settings.vue`
  - Preferences notifications
  - Configuration compte

- [ ] **3.2.6** Completer page Calendar
  - Fichier: `resources/src/views/Calendar.vue`
  - Afficher campagnes planifiees
  - Vue mensuelle/hebdomadaire

---

## Phase 4: Ameliorations (Priorite BASSE)

### 4.1 Performance

- [ ] **4.1.1** Implementer queue pour envoi SMS
  - Creer Job SendSmsJob
  - Traitement asynchrone

- [ ] **4.1.2** Implementer queue pour webhooks
  - Creer Job TriggerWebhookJob
  - Retry logic avec backoff

- [ ] **4.1.3** Caching analytics
  - Cache dashboard data
  - Invalidation intelligente

### 4.2 Features Additionnelles

- [ ] **4.2.1** Clonage de campagnes
  - Endpoint `POST /campaigns/{id}/clone`
  - Bouton UI

- [ ] **4.2.2** Export contacts
  - Endpoint `GET /contacts/export`
  - Format CSV/Excel

- [ ] **4.2.3** Templates partages
  - Systeme de permissions
  - Templates publics/prives

- [ ] **4.2.4** Rapports planifies par email
  - Configuration frequence
  - Selection metriques

### 4.3 Tests

- [ ] **4.3.1** Tests unitaires Models
- [ ] **4.3.2** Tests unitaires Services
- [ ] **4.3.3** Tests Feature Controllers
- [ ] **4.3.4** Tests E2E Frontend

---

## Suivi de Progression

| Phase | Items | Completes | % |
|-------|-------|-----------|---|
| Phase 1 | 8 | 8 | 100% |
| Phase 2 | 7 | 7 | 100% |
| Phase 3 | 10 | 0 | 0% |
| Phase 4 | 10 | 0 | 0% |
| **Total** | **35** | **15** | **43%** |

---

## Notes d'Execution

### Ordre Recommande
1. Phase 1.1 (Bugs) -> Phase 1.2 (Securite)
2. Phase 2.1 -> Phase 2.2 -> Phase 2.3
3. Phase 3.1 -> Phase 3.2
4. Phase 4 (au fil du temps)

### Estimation Temps
- Phase 1: 2-3 jours
- Phase 2: 2-3 jours
- Phase 3: 5-7 jours
- Phase 4: Ongoing

### Commande de Deploiement
```bash
cd /var/www/sendwave-pro
sudo bash deploy.sh
```

---

*Derniere mise a jour: Janvier 2026*
