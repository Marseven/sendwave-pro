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

- [x] **3.1.1** Page Blacklist Management ✅
  - Fichier: `resources/src/views/Blacklist.vue`
  - CRUD numéros bloqués avec pagination
  - Vérification de numéro, export CSV
  - Statistiques (total, ce mois, SMS évités)

- [x] **3.1.2** Page SMS Configuration ✅
  - Fichier: `resources/src/views/SmsConfig.vue`
  - Configuration Airtel (HTTP API) et Moov (SMPP)
  - Toggle activation, test de connexion
  - Formulaires séparés par opérateur

- [x] **3.1.3** Page API Keys Management ✅
  - Existant: `resources/src/views/ApiIntegrations.vue`
  - Route `/api-keys` déjà fonctionnelle

- [x] **3.1.4** Page Audit Logs Viewer ✅
  - Fichier: `resources/src/views/AuditLogs.vue`
  - Filtres par action/date, pagination
  - Détails avec diff old/new values
  - Export CSV

### 3.2 Features a Completer

- [x] **3.2.1** UI Planification Campagnes ✅
  - Fichier: `resources/src/views/CampaignCreate.vue`
  - Date picker avec date minimum (aujourd'hui)
  - Options récurrence: quotidienne, hebdomadaire (sélection jours), mensuelle
  - Date de fin optionnelle pour récurrence
  - Intégration API schedules

- [x] **3.2.2** UI A/B Testing ✅
  - Fichier: `resources/src/views/CampaignCreate.vue`
  - Checkbox pour activer le test A/B
  - Gestion de 2-4 variantes de messages
  - Affichage du pourcentage par variante
  - Récapitulatif avec toutes les variantes
  - Envoi des variantes à l'API

- [x] **3.2.3** UI Variables Templates ✅
  - Fichier: `resources/src/views/Templates.vue`
  - Liste variables avec descriptions (grille 2 colonnes)
  - Preview en temps réel avec remplacement par exemples
  - Affichage nombre de caractères et SMS dans l'aperçu

- [x] **3.2.4** UI Export Analytics ✅
  - Fichier: `resources/src/views/Reports.vue`
  - Boutons export PDF/Excel/CSV
  - Sélection période (date début/fin)
  - Service analyticsService avec méthodes export
  - Téléchargement automatique des fichiers

- [x] **3.2.5** Completer page Settings ✅
  - Fichier: `resources/src/views/Settings.vue`
  - Notifications: alertes campagnes, crédit faible
  - Préférences régionales: langue, fuseau horaire
  - Préférences SMS: signature, seuil alerte crédit

- [x] **3.2.6** Completer page Calendar ✅
  - Fichier: `resources/src/views/Calendar.vue`
  - 3 modes de vue: Liste, Semaine, Mois
  - Grille calendrier mensuelle avec navigation
  - Vue hebdomadaire avec jours et campagnes
  - Affichage des campagnes sur chaque jour
  - Navigation mois précédent/suivant

---

## Phase 4: Ameliorations (Priorite BASSE)

### 4.1 Performance

- [x] **4.1.1** Implementer queue pour envoi SMS ✅
  - Créé: `app/Jobs/SendSmsJob.php`
  - 3 tentatives avec backoff (30s, 60s, 120s)
  - Sauvegarde automatique dans l'historique
  - Déclenchement webhooks après envoi

- [x] **4.1.2** Implementer queue pour webhooks ✅
  - Créé: `app/Jobs/TriggerWebhookJob.php`
  - 5 tentatives avec backoff exponentiel
  - Désactivation auto après 10 échecs consécutifs
  - Méthode `triggerAsync()` dans WebhookService

- [x] **4.1.3** Caching analytics ✅
  - Cache 5 min pour dashboard, 15 min pour rapports
  - Méthode `invalidateDashboardCache()` pour invalidation
  - Auto-invalidation après `updateDailyAnalytics()`

### 4.2 Features Additionnelles

- [x] **4.2.1** Clonage de campagnes ✅
  - Endpoint `POST /campaigns/{id}/clone`
  - Clone variantes A/B avec la campagne
  - Bouton dans l'historique des campagnes
  - Méthode clone() dans campaignService.ts

- [x] **4.2.2** Export contacts ✅
  - Endpoint `GET /contacts/export` avec filtres (group, status)
  - Format CSV avec escape automatique
  - Bouton export dans la page Contacts
  - Méthode exportCsv() dans contactService.ts

- [x] **4.2.3** Templates partages ✅
  - Champ `is_public` dans MessageTemplate
  - Endpoint `POST /templates/{id}/toggle-public`
  - Liste incluant templates publics d'autres utilisateurs
  - Méthode togglePublic() dans templateService.ts

- [x] **4.2.4** Rapports planifies par email ✅
  - Job `SendScheduledReportJob` avec fréquence configurable
  - Rapports hebdomadaires (lundi 8h) et mensuels (1er du mois)
  - Email formaté avec résumé, opérateurs, top campagnes
  - Basé sur préférence `weekly_reports` de l'utilisateur

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
| Phase 3 | 10 | 10 | 100% |
| Phase 4 | 10 | 7 | 70% |
| **Total** | **35** | **32** | **91%** |

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
