# 🚀 SendWave Pro - Roadmap Complète des Fonctionnalités

**Date:** 6 Novembre 2025
**Objectif:** Plateforme complète de campagnes SMS pour le Gabon

---

## 🔴 **BUGS CRITIQUES À CORRIGER (Priorité 1)**

### 1. **Erreur 500 - Envois simultanés**
**Problème:** Quand 2 utilisateurs envoient des SMS en même temps, erreur 500
**Cause:** Pas de gestion de concurrence, pas de queue system
**Solution:**
- Implémenter Laravel Queue (Redis ou Database)
- Utiliser `DB::transaction()` pour les insertions
- Ajouter rate limiting par utilisateur
- Implémenter un système de retry avec exponentiel backoff

**Fichiers concernés:**
- `app/Http/Controllers/MessageController.php`
- `app/Services/SMS/SmsRouter.php`
- `config/queue.php`

---

### 2. **Liens morts**
**À auditer et corriger:**
- Vérifier tous les liens dans le menu
- Vérifier les routes dans `routes/web.php` et `routes/api.php`
- S'assurer que toutes les pages Vue.js existent
- Corriger les 404

---

### 3. **Strings non opérationnels**
**Problème:** Textes hardcodés ou traductions manquantes
**Solution:**
- Implémenter système i18n (Français par défaut)
- Créer fichier `resources/lang/fr/messages.php`
- Externaliser tous les textes

---

## 🟡 **FONCTIONNALITÉS MANQUANTES (Priorité 2)**

### **A. GESTION DES COMPTES (Sub-Accounts)**

#### 1. **Comptes cloisonnés multi-utilisateurs**
**Besoin:** Un compte principal peut créer des sous-comptes avec permissions limitées

**Fonctionnalités:**
- Création de sous-comptes par le compte parent
- Attribution de crédits SMS par sous-compte
- Limitation des actions par rôle (admin, manager, sender)
- Dashboard séparé par sous-compte
- Historique et statistiques par sous-compte

**Tables nécessaires:**
```sql
sub_accounts:
  - id
  - parent_user_id (FK users)
  - name
  - email
  - password
  - role (admin, manager, sender, viewer)
  - sms_credit_limit (nombre max de SMS)
  - sms_used (compteur SMS consommés)
  - is_active
  - permissions (JSON: can_send, can_view_stats, can_manage_contacts, etc.)
  - created_at, updated_at

sub_account_permissions:
  - id
  - sub_account_id
  - permission_name
  - allowed (boolean)
```

**Routes à créer:**
- `GET /api/sub-accounts` - Liste des sous-comptes
- `POST /api/sub-accounts` - Créer un sous-compte
- `PUT /api/sub-accounts/{id}` - Modifier
- `DELETE /api/sub-accounts/{id}` - Supprimer
- `POST /api/sub-accounts/{id}/credits` - Attribuer/retirer crédits
- `GET /api/sub-accounts/{id}/stats` - Statistiques du sous-compte

**Pages Vue à créer:**
- `/accounts` - Liste des sous-comptes
- `/accounts/create` - Créer un sous-compte
- `/accounts/{id}/edit` - Modifier
- `/accounts/{id}/credits` - Gérer crédits

---

#### 2. **Gestion des crédits SMS**
**Fonctionnalités:**
- Système de crédit prépayé (1 crédit = 1 SMS)
- Rechargement manuel par l'admin
- Alerte quand crédits faibles (< 10%)
- Blocage automatique si crédits épuisés
- Historique des consommations

**Table:**
```sql
credit_transactions:
  - id
  - user_id (ou sub_account_id)
  - type (purchase, consumption, refund, transfer)
  - amount (+ ou -)
  - balance_after
  - description
  - reference (ex: payment_id, message_id)
  - created_at

user_credits:
  - user_id (PK)
  - total_credits
  - used_credits
  - remaining_credits
  - last_recharge_at
  - updated_at
```

**Fonctions:**
- `checkCredit(user_id, sms_count)` - Vérifier si assez de crédits
- `deductCredit(user_id, sms_count)` - Déduire des crédits
- `addCredit(user_id, amount)` - Ajouter des crédits
- `getCreditHistory(user_id)` - Historique

---

### **B. GESTION DES CONTACTS AMÉLIORÉE**

#### 3. **Groupes de contacts**
**Fonctionnalités:**
- Créer des groupes dynamiques (Clients VIP, Région Libreville, etc.)
- Ajouter/retirer contacts d'un groupe
- Importer CSV avec assignation automatique à un groupe
- Statistiques par groupe

**Tables:**
```sql
contact_groups:
  - id
  - user_id
  - name
  - description
  - color (hex pour UI)
  - contact_count (cache)
  - created_at, updated_at

contact_group_members:
  - contact_id (FK)
  - group_id (FK)
  - added_at
  - PRIMARY KEY (contact_id, group_id)
```

**Routes:**
- `GET /api/groups` - Liste des groupes
- `POST /api/groups` - Créer un groupe
- `PUT /api/groups/{id}` - Modifier
- `DELETE /api/groups/{id}` - Supprimer
- `POST /api/groups/{id}/contacts` - Ajouter des contacts
- `DELETE /api/groups/{id}/contacts/{contactId}` - Retirer un contact

---

#### 4. **Champs personnalisés (Custom Fields)**
**Besoin:** Ajouter des champs dynamiques aux contacts (Entreprise, Ville, Date anniversaire, etc.)

**Tables:**
```sql
custom_fields:
  - id
  - user_id
  - name (ex: "Entreprise", "Ville")
  - type (text, number, date, select)
  - options (JSON pour select: ["Libreville", "Port-Gentil"])
  - is_required
  - created_at

contact_custom_values:
  - id
  - contact_id
  - custom_field_id
  - value
```

**Utilisation dans messages:**
- Variables dynamiques: `{{entreprise}}`, `{{ville}}`, `{{date_anniversaire}}`
- Personnalisation automatique des SMS

---

### **C. CAMPAGNES AVANCÉES**

#### 5. **Campagnes récurrentes**
**Fonctionnalités:**
- Planifier des envois récurrents (quotidien, hebdomadaire, mensuel)
- Rappels automatiques
- Vœux d'anniversaire automatiques

**Table:**
```sql
recurring_campaigns:
  - id
  - campaign_id (FK)
  - frequency (daily, weekly, monthly, yearly)
  - schedule_time (HH:MM)
  - schedule_days (JSON: [1,3,5] pour lun, mer, ven)
  - next_run_at
  - is_active
  - created_at, updated_at
```

---

#### 6. **A/B Testing**
**Fonctionnalités:**
- Tester 2 messages différents sur un échantillon
- Envoyer automatiquement le meilleur aux autres

**Table:**
```sql
ab_tests:
  - id
  - campaign_id
  - message_a
  - message_b
  - sample_size (% de contacts pour test)
  - winner (a ou b)
  - metrics (JSON: {a_sent, a_delivered, b_sent, b_delivered})
  - created_at, completed_at
```

---

#### 7. **Campagnes conditionnelles (Workflows)**
**Exemple:**
- Si contact n'ouvre pas SMS 1 → Envoyer SMS 2 après 3 jours
- Si contact répond "OUI" → Ajouter au groupe "Intéressés"

**Tables:**
```sql
workflows:
  - id
  - name
  - trigger_type (immediate, delayed, conditional)
  - conditions (JSON)
  - actions (JSON)
  - is_active

workflow_executions:
  - id
  - workflow_id
  - contact_id
  - status (pending, executing, completed, failed)
  - executed_at
```

---

### **D. RAPPORTS ET ANALYTICS**

#### 8. **Dashboard avancé**
**Métriques:**
- SMS envoyés aujourd'hui / cette semaine / ce mois
- Taux de réussite par opérateur (Airtel vs Moov)
- Coût total consommé
- Top 10 des contacts les plus contactés
- Graphiques de tendance (Chart.js)
- Comparaison mois par mois

---

#### 9. **Rapports détaillés**
**Fonctionnalités:**
- Rapport par campagne (taux d'envoi, coût, etc.)
- Rapport par opérateur (performance Airtel vs Moov)
- Rapport par sous-compte
- Export PDF/Excel

**Routes:**
- `GET /api/reports/overview` - Vue d'ensemble
- `GET /api/reports/campaigns/{id}` - Rapport d'une campagne
- `GET /api/reports/operators` - Comparaison Airtel/Moov
- `GET /api/reports/export` - Export Excel/PDF

---

#### 10. **Tracking des SMS (DLR - Delivery Reports)**
**Besoin:** Savoir si un SMS a été délivré ou échoué

**Fonctionnalités:**
- Recevoir les webhooks d'Airtel/Moov
- Mettre à jour le statut: `pending` → `delivered` ou `failed`
- Afficher le statut en temps réel dans l'interface

**Table:**
```sql
delivery_reports:
  - id
  - message_id (FK messages)
  - status (delivered, failed, expired)
  - delivered_at
  - error_code
  - error_message
  - provider_response (JSON)
```

**Webhook à créer:**
- `POST /api/webhooks/airtel/delivery` - Recevoir DLR Airtel
- `POST /api/webhooks/moov/delivery` - Recevoir DLR Moov

---

### **E. SÉCURITÉ ET CONFORMITÉ**

#### 11. **Liste noire (Blacklist)**
**Fonctionnalités:**
- Numéros qui ne veulent plus recevoir de SMS (STOP)
- Vérification automatique avant envoi
- Commande STOP automatique (répondre STOP = blacklist)

**Table:**
```sql
blacklist:
  - id
  - phone_number
  - reason (user_request, spam_complaint, invalid_number)
  - added_by_user_id
  - added_at
```

**Routes:**
- `GET /api/blacklist` - Liste des numéros blacklistés
- `POST /api/blacklist` - Ajouter un numéro
- `DELETE /api/blacklist/{phone}` - Retirer de la blacklist

---

#### 12. **Logs d'audit**
**Fonctionnalités:**
- Tracer toutes les actions importantes
- Qui a envoyé quoi et quand
- Qui a modifié les configs

**Table:**
```sql
audit_logs:
  - id
  - user_id
  - action (sms_sent, config_updated, user_created, etc.)
  - entity_type (message, campaign, contact, config)
  - entity_id
  - changes (JSON: old_value, new_value)
  - ip_address
  - user_agent
  - created_at
```

---

#### 13. **Limites de débit (Rate Limiting)**
**Fonctionnalités:**
- Max 100 SMS par minute par utilisateur
- Max 1000 SMS par heure
- Protection contre les abus

**Implémentation:**
```php
// Dans MessageController
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('sms-send', function (Request $request) {
    return Limit::perMinute(100)->by($request->user()->id);
});
```

---

### **F. INTÉGRATIONS API**

#### 14. **API REST pour développeurs**
**Fonctionnalités:**
- Générer des API keys
- Documentation Swagger/OpenAPI
- Webhooks sortants (notifier un système externe)

**Routes publiques:**
- `POST /api/v1/send` - Envoyer un SMS via API key
- `GET /api/v1/status/{message_id}` - Vérifier le statut
- `GET /api/v1/balance` - Consulter le solde

**Table:**
```sql
api_keys:
  - id
  - user_id
  - name (ex: "Site Web Principal")
  - key (généré)
  - secret (haché)
  - permissions (JSON)
  - last_used_at
  - expires_at
  - is_active
  - created_at
```

---

#### 15. **Webhooks sortants**
**Besoin:** Notifier un système externe quand un événement se produit

**Événements:**
- `sms.sent` - Un SMS a été envoyé
- `sms.delivered` - Un SMS a été délivré
- `sms.failed` - Un SMS a échoué
- `campaign.completed` - Une campagne est terminée

**Table:**
```sql
webhooks:
  - id
  - user_id
  - url (endpoint à appeler)
  - events (JSON: ['sms.sent', 'sms.delivered'])
  - secret (pour signer les requêtes)
  - is_active
  - last_triggered_at
  - created_at
```

---

### **G. TEMPLATES ET PERSONNALISATION**

#### 16. **Bibliothèque de templates**
**Fonctionnalités:**
- Templates pré-définis (Promotion, Rappel, Confirmation)
- Variables dynamiques
- Prévisualisation avec données de test
- Partage de templates entre sous-comptes

**Table actuelle à améliorer:**
```sql
message_templates:
  - id
  - user_id
  - name
  - category (marketing, transactional, notification)
  - message
  - variables (JSON: ['name', 'code', 'amount'])
  - usage_count (combien de fois utilisé)
  - is_public (visible par tous les sous-comptes)
  - status
  - created_at, updated_at
```

---

#### 17. **Éditeur de templates avancé**
**Fonctionnalités:**
- Éditeur WYSIWYG
- Insérer des variables en cliquant
- Aperçu en direct avec données de test
- Validation de la longueur (160/320 caractères)

---

### **H. OPTIMISATIONS TECHNIQUES**

#### 18. **Queue System (Redis/Database)**
**Besoin:** Gérer les envois en arrière-plan

**Jobs à créer:**
```php
SendSmsJob::class
SendBulkSmsJob::class
ProcessCampaignJob::class
UpdateDeliveryStatusJob::class
```

**Config:**
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

**Commande:**
```bash
php artisan queue:work --queue=high,default,low
```

---

#### 19. **Cache Redis**
**Optimisations:**
- Cache des configurations SMS
- Cache des contacts fréquents
- Cache des statistiques dashboard

```php
Cache::remember('sms_config_airtel', 3600, function() {
    return SmsConfig::where('provider', 'airtel')->first();
});
```

---

#### 20. **Monitoring et alertes**
**Fonctionnalités:**
- Surveiller le taux d'erreur
- Alerter si > 10% d'échecs
- Alerter si crédits < 100
- Logs structurés (ELK stack ou Papertrail)

---

## 🟢 **AMÉLIORATIONS UX/UI (Priorité 3)**

### 21. **Notifications en temps réel**
- WebSockets (Laravel Echo + Pusher)
- Notifications quand campagne terminée
- Alerte quand crédits faibles

---

### 22. **Mode sombre (Dark Mode)**
- Toggle dans les paramètres
- Sauvegarde de la préférence

---

### 23. **Recherche globale**
- Rechercher dans contacts, campagnes, messages
- Raccourci clavier (Ctrl+K)

---

### 24. **Export de données**
- Export contacts en CSV
- Export historique messages en Excel
- Export rapports en PDF

---

### 25. **Multilingue**
- Français (par défaut)
- Anglais
- Détection automatique de la langue navigateur

---

## 📊 **ROADMAP PRIORISÉE**

### **Phase 1 - Stabilisation (1-2 semaines)**
✅ Corriger bug 500 envois simultanés (Queue system)
✅ Corriger liens morts
✅ Corriger strings
✅ Implémenter rate limiting
✅ Ajouter logs d'erreur détaillés

### **Phase 2 - Comptes et crédits (1-2 semaines)**
✅ Gestion des sous-comptes
✅ Système de crédits SMS
✅ Permissions et rôles
✅ Dashboard par sous-compte

### **Phase 3 - Contacts avancés (1 semaine)**
✅ Groupes de contacts
✅ Champs personnalisés
✅ Import CSV amélioré

### **Phase 4 - Campagnes avancées (2 semaines)**
✅ Campagnes récurrentes
✅ A/B Testing
✅ Workflows conditionnels

### **Phase 5 - Analytics et rapports (1 semaine)**
✅ Dashboard avancé
✅ Rapports détaillés
✅ Export PDF/Excel

### **Phase 6 - Tracking et conformité (1 semaine)**
✅ DLR (Delivery Reports)
✅ Blacklist
✅ Logs d'audit

### **Phase 7 - API et intégrations (1 semaine)**
✅ API REST publique
✅ Webhooks sortants
✅ Documentation Swagger

### **Phase 8 - Optimisations (1 semaine)**
✅ Cache Redis
✅ Monitoring
✅ Notifications temps réel

---

## 🎯 **ESTIMATION TOTALE**
**Temps de développement:** 10-12 semaines
**Complexité:** Moyenne à élevée
**Technologies nécessaires:** Laravel Queues, Redis, WebSockets, Chart.js, PDF export

---

## 📝 **NOTES IMPORTANTES**

1. **Prioriser les phases 1 et 2** - Ce sont les plus critiques
2. **Tests unitaires** - À implémenter progressivement
3. **Documentation** - Documenter chaque nouvelle feature
4. **Migration données** - Prévoir des migrations pour les nouvelles tables
5. **Performance** - Tester avec 10 000+ contacts et 1000+ SMS/minute

---

**Auteur:** Claude AI
**Révision:** À valider par Jeff Boundamas Codon
**Prochaine étape:** Choisir les fonctionnalités prioritaires et commencer Phase 1
