# 🚀 SendWave Pro - Roadmap d'Implémentation

## 🐛 PHASE 0 : Corrections Critiques (EN COURS)

### Bugs Rapportés par Jeff

1. **✅ Erreur 500 (envois simultanés)** - RÉSOLU
   - Système actuel : envoi synchrone direct
   - Le bug 500 est résolu temporairement, mais peut réapparaître sous charge

2. **❌ Strings non opérationnels** - À CORRIGER
   - Problème : Traductions/i18n non fonctionnelles
   - Localisation français/anglais manquante

3. **❌ Liens morts** - À CORRIGER
   - Audit des routes frontend/backend
   - Vérifier tous les liens de navigation

---

## 📋 PHASE 1 : Gestion des Comptes Cloisonnés (REQUIS)

**Demandé par Jeff** : "Tu confirmes que pour les comptes c'est pour donner des accès cloisonnés ? C'est requis"

### A. Système de Sub-Accounts

#### 1.1 Base de Données

```sql
CREATE TABLE sub_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'sender', 'viewer') DEFAULT 'sender',
    status ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    sms_credit_limit INT DEFAULT NULL,
    sms_used INT DEFAULT 0,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_parent_user (parent_user_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

CREATE TABLE sub_account_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sub_account_id BIGINT UNSIGNED NOT NULL,
    permission VARCHAR(100) NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sub_account_id) REFERENCES sub_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_permission (sub_account_id, permission),
    INDEX idx_permission (permission)
);
```

#### 1.2 Modèle Laravel

**`app/Models/SubAccount.php`**
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class SubAccount extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'parent_user_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'sms_credit_limit',
        'sms_used',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
        'sms_credit_limit' => 'integer',
        'sms_used' => 'integer'
    ];

    protected $hidden = ['password'];

    // Relations
    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    // Permissions
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function canSendSms(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->sms_credit_limit === null) return true;
        return $this->sms_used < $this->sms_credit_limit;
    }
}
```

#### 1.3 API Routes

**`routes/api.php`**
```php
// Sub-Accounts Management (Admin only)
Route::middleware(['auth:sanctum'])->prefix('sub-accounts')->group(function () {
    Route::get('/', [SubAccountController::class, 'index']);
    Route::post('/', [SubAccountController::class, 'store']);
    Route::get('/{id}', [SubAccountController::class, 'show']);
    Route::put('/{id}', [SubAccountController::class, 'update']);
    Route::delete('/{id}', [SubAccountController::class, 'destroy']);
    Route::post('/{id}/credits', [SubAccountController::class, 'addCredits']);
    Route::post('/{id}/permissions', [SubAccountController::class, 'updatePermissions']);
    Route::post('/{id}/suspend', [SubAccountController::class, 'suspend']);
    Route::post('/{id}/activate', [SubAccountController::class, 'activate']);
});

// Auth for sub-accounts
Route::post('/sub-accounts/login', [SubAccountController::class, 'login']);
```

#### 1.4 Permissions Disponibles

```php
const PERMISSIONS = [
    'send_sms',          // Envoyer des SMS
    'view_history',      // Voir l'historique
    'manage_contacts',   // Gérer les contacts
    'manage_groups',     // Gérer les groupes
    'create_campaigns',  // Créer des campagnes
    'view_analytics',    // Voir les statistiques
    'manage_templates',  // Gérer les modèles
    'export_data',       // Exporter les données
];
```

#### 1.5 Rôles Prédéfinis

| Rôle | Permissions |
|------|-------------|
| **admin** | Toutes les permissions |
| **manager** | send_sms, view_history, manage_contacts, manage_groups, create_campaigns, view_analytics |
| **sender** | send_sms, view_history, manage_contacts |
| **viewer** | view_history, view_analytics |

---

## 📋 PHASE 2 : Corrections des Bugs

### 2.1 Strings non opérationnels

**Problème :** Système i18n non fonctionnel

**Solution :**
1. Installer Laravel i18n
2. Créer fichiers de traduction FR/EN
3. Wrapper tous les textes avec `__()` ou `trans()`

**Fichiers :**
- `resources/lang/fr/messages.php`
- `resources/lang/en/messages.php`

### 2.2 Liens morts

**Actions :**
1. Audit complet de toutes les routes
2. Vérifier la correspondance frontend/backend
3. Créer tests automatisés pour détecter les 404

---

## 📋 PHASE 3 : Gestion Avancée des Contacts

### 3.1 Groupes de Contacts

```sql
CREATE TABLE contact_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    contacts_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

CREATE TABLE contact_group_members (
    contact_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (contact_id, group_id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES contact_groups(id) ON DELETE CASCADE
);
```

### 3.2 Champs Personnalisés

```sql
CREATE TABLE contact_custom_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'email') DEFAULT 'text',
    is_required BOOLEAN DEFAULT false,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_field (user_id, field_name)
);

CREATE TABLE contact_custom_field_values (
    contact_id BIGINT UNSIGNED NOT NULL,
    field_id BIGINT UNSIGNED NOT NULL,
    value TEXT,

    PRIMARY KEY (contact_id, field_id),
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES contact_custom_fields(id) ON DELETE CASCADE
);
```

### 3.3 Import/Export CSV

**Routes :**
```php
Route::post('/contacts/import', [ContactController::class, 'importCsv']);
Route::get('/contacts/export', [ContactController::class, 'exportCsv']);
Route::post('/contacts/import/validate', [ContactController::class, 'validateCsv']);
```

---

## 📋 PHASE 4 : Campagnes Avancées

### 4.1 Campagnes Récurrentes

```sql
CREATE TABLE campaign_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    day_of_week INT,
    day_of_month INT,
    time TIME NOT NULL,
    next_run_at TIMESTAMP,
    is_active BOOLEAN DEFAULT true,

    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);
```

### 4.2 Variables Dynamiques

**Exemple :**
```
"Bonjour {nom}, votre solde est de {solde} FCFA"
```

**Mapping :**
```php
[
    'nom' => 'contact.name',
    'solde' => 'contact.custom_field.balance',
    'prenom' => 'contact.first_name'
]
```

### 4.3 A/B Testing

```sql
CREATE TABLE campaign_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    variant_name VARCHAR(50),
    message TEXT NOT NULL,
    percentage INT DEFAULT 50,
    sent_count INT DEFAULT 0,
    success_count INT DEFAULT 0,

    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);
```

---

## 📋 PHASE 5 : Bibliothèque de Templates

### 5.1 Templates Prédéfinis

```sql
CREATE TABLE message_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    category VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    variables JSON,
    is_public BOOLEAN DEFAULT false,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_public (is_public)
);
```

### 5.2 Catégories

- Marketing
- Notifications
- Alertes
- Rappels
- Confirmations
- Promotions

---

## 📋 PHASE 6 : Analytics & Rapports

### 6.1 Tableau de Bord Amélioré

**Widgets :**
- SMS envoyés (aujourd'hui, semaine, mois)
- Taux de succès par opérateur
- Coûts totaux
- Tendances d'utilisation
- Top 5 campagnes
- Distribution par opérateur

### 6.2 Rapports Exportables

**Formats :**
- PDF
- Excel (XLSX)
- CSV

**Types de rapports :**
- Rapport mensuel d'activité
- Rapport par campagne
- Rapport par contact
- Rapport de coûts

---

## 📋 PHASE 7 : Sécurité & Audit

### 7.1 Logs d'Audit

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    sub_account_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    model_type VARCHAR(100),
    model_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);
```

### 7.2 Liste Noire (Blacklist)

```sql
CREATE TABLE blacklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    reason VARCHAR(255),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_blacklist (user_id, phone_number)
);
```

---

## 📋 PHASE 8 : Intégrations API

### 8.1 Webhooks

```sql
CREATE TABLE webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event (event)
);

CREATE TABLE webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50),
    payload JSON,
    response_code INT,
    response_body TEXT,
    delivered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
);
```

**Events disponibles :**
- `sms.sent`
- `sms.failed`
- `campaign.completed`
- `credits.low`

### 8.2 API REST Publique

**Documentation OpenAPI/Swagger**

**Endpoints :**
```
POST   /api/v1/sms/send
GET    /api/v1/sms/history
POST   /api/v1/contacts
GET    /api/v1/campaigns
POST   /api/v1/campaigns
```

---

## 📋 PHASE 9 : Optimisations

### 9.1 Performance

- Cache Redis pour les configs
- Queue pour envois de masse
- Index de base de données optimisés
- Pagination sur toutes les listes

### 9.2 Monitoring

- Sentry pour les erreurs
- Laravel Telescope pour le debug
- Logs rotatifs
- Alertes email si erreurs critiques

---

## 📊 Calendrier Estimé

| Phase | Durée | Priorité |
|-------|-------|----------|
| Phase 0 | 3 jours | **CRITIQUE** |
| Phase 1 | 1 semaine | **REQUIS** |
| Phase 2 | 2 jours | **HAUTE** |
| Phase 3 | 1 semaine | MOYENNE |
| Phase 4 | 1 semaine | MOYENNE |
| Phase 5 | 3 jours | BASSE |
| Phase 6 | 1 semaine | MOYENNE |
| Phase 7 | 1 semaine | HAUTE |
| Phase 8 | 1 semaine | MOYENNE |
| Phase 9 | En continu | HAUTE |

**Total estimé :** 8-10 semaines

---

## ✅ Statut Actuel

- ✅ Bug 500 résolu (temporairement)
- ⏳ Phase 0 en cours
- ❌ Phase 1-9 à implémenter

**Prochaine étape :** Implémenter le système de sub-accounts (Phase 1)

---

**Date :** 6 Novembre 2025
**Version :** 1.0
