# PLAN D'ACTION - SendWave Pro / JOBS SMS
## Mise en conformité avec le Cahier des Charges

> **Date:** 25 Janvier 2026
> **Projet:** SendWave Pro (rebrand: JOBS SMS)
> **Stack:** Laravel 11 + Vue 3 + Tailwind CSS
> **Design:** Thème Bleu (inspiré maquettes OrbiTel)
> **Développement:** Claude Code

---

## RÉSUMÉ EXÉCUTIF

| Métrique | Valeur |
|----------|--------|
| Fonctionnalités OK | 49% |
| Fonctionnalités Partielles | 34% |
| Fonctionnalités Manquantes | 17% |
| Effort estimé | 15-20 jours |

---

## DIRECTIVES DESIGN (Basées sur maquettes OrbiTel)

### Palette de couleurs à conserver
```css
/* Variables Tailwind à définir */
--primary-blue: #1E40AF;      /* Bleu principal */
--primary-blue-light: #3B82F6; /* Bleu hover */
--primary-blue-dark: #1E3A8A;  /* Bleu foncé */
--success-green: #22C55E;      /* Statuts OK/Approved */
--warning-yellow: #EAB308;     /* Alertes */
--danger-red: #EF4444;         /* Erreurs */
--bg-sidebar: #1F2937;         /* Sidebar sombre */
--bg-main: #F9FAFB;            /* Fond principal */
```

### Éléments UI à implémenter (depuis maquettes)
1. **Sidebar gauche** avec navigation par icônes
2. **Tabs horizontaux** pour sous-sections (Send SMS | Send Opt SMS | SMS From File)
3. **Boutons d'action** en haut à droite (Download, Share, + Create Campaign)
4. **Tableaux** avec colonnes triables et actions par ligne
5. **Modals** pour templates, scheduling, confirmations
6. **Cards statistiques** sur le dashboard

---

## PHASE 1 - PRIORITÉ HAUTE (Semaine 1-2)

### 1.1 Comptabilité Analytique Complète

**Objectif:** Chaque SMS génère une ligne analytique traçable

#### Backend (Laravel)

```bash
# Migration à créer
php artisan make:migration create_sms_analytics_table
```

**Schema:** `database/migrations/xxxx_create_sms_analytics_table.php`
```php
Schema::create('sms_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('sub_account_id')->nullable()->constrained();
    $table->foreignId('campaign_id')->nullable()->constrained();
    $table->foreignId('message_id')->constrained();
    $table->string('api_key_id')->nullable();
    $table->string('country_code', 3)->default('GA');
    $table->string('operator')->nullable(); // airtel, moov
    $table->string('gateway')->nullable();  // airtel_http, moov_smpp
    $table->string('message_type')->default('transactional'); // transactional, marketing
    $table->decimal('unit_cost', 10, 2);
    $table->integer('sms_parts')->default(1);
    $table->decimal('total_cost', 10, 2);
    $table->string('status'); // sent, delivered, failed
    $table->string('period_key'); // 2026-01 (pour clôture)
    $table->boolean('is_closed')->default(false);
    $table->timestamps();

    $table->index(['user_id', 'period_key']);
    $table->index(['sub_account_id', 'period_key']);
});
```

**Service:** `app/Services/AnalyticsRecordService.php`
- Enregistrer chaque SMS envoyé avec tous les détails
- Détecter automatiquement le pays via le préfixe
- Calculer le coût unitaire et total

---

### 1.2 Système de Clôture Périodique

**Objectif:** Clôture mensuelle automatique avec gel des données

**Schema:** `database/migrations/xxxx_create_period_closures_table.php`
```php
Schema::create('period_closures', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('period_key'); // 2026-01
    $table->integer('total_sms');
    $table->decimal('total_cost', 12, 2);
    $table->json('breakdown_by_subaccount')->nullable();
    $table->json('breakdown_by_operator')->nullable();
    $table->json('breakdown_by_type')->nullable();
    $table->enum('status', ['pending', 'closed', 'adjusted']);
    $table->timestamp('closed_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'period_key']);
});
```

**Service:** `app/Services/PeriodClosureService.php`
- Calculer les totaux par période
- Générer les ventilations (par sous-compte, opérateur, type)
- Marquer les analytics comme clôturées

**Commande:** `app/Console/Commands/ClosePeriodCommand.php`
```bash
php artisan sms:close-period --period=2026-01
```

**Scheduler:** Clôture automatique le 1er de chaque mois à 00:30

---

### 1.3 Budgets par Sous-compte

**Objectif:** Plafonds mensuels avec alertes

**Migration:** Ajouter à `sub_accounts`
```php
$table->decimal('monthly_budget', 12, 2)->nullable();
$table->decimal('budget_alert_threshold', 5, 2)->default(80); // Alerte à 80%
$table->boolean('block_on_budget_exceeded')->default(false);
```

**Service:** `app/Services/BudgetService.php`
- Vérifier le budget avant chaque envoi
- Déclencher des événements (BudgetAlertEvent, BudgetExceededEvent)
- Bloquer si dépassement et option activée

---

### 1.4 Clés API par Sous-compte

**Objectif:** Rattacher les API keys aux sous-comptes

**Migration:** Ajouter à `api_keys`
```php
$table->foreignId('sub_account_id')->nullable()->constrained();
```

---

## PHASE 2 - PRIORITÉ MOYENNE (Semaine 2-3)

### 2.1 Interface Envoi SMS (Inspirée maquettes OrbiTel)

**Objectif:** Refonte de l'interface d'envoi avec 3 onglets

**Page:** `resources/src/views/SendSms.vue`

**Onglets:**
1. **Send SMS** - Envoi rapide avec sélection channel/route/sender
2. **Send Opt SMS** - Envoi avec options avancées
3. **SMS From File** - Import fichier pour envoi en masse

**Fonctionnalités:**
- Sélection Message Channel (Transactional/Marketing)
- Sélection Message Route (Airtel/Moov/Auto)
- Sélection Sender Id
- Compteur caractères et SMS
- Option "Save as draft"
- Boutons "Send Now" et "Schedule for later"
- Panneau latéral: Group, File, Template

---

### 2.2 Section Transactional (4 onglets)

**Page:** `resources/src/views/Transactional.vue`

**Onglets:**
1. **Sender Id** - Gestion des identifiants d'expéditeur
2. **Templates** - Modèles de messages
3. **Drafts** - Brouillons sauvegardés
4. **Routes** - Configuration des routes/passerelles

---

### 2.3 Section Database (Contacts & Groupes)

**Page:** `resources/src/views/Database.vue`

**Onglets:**
1. **My Groups** - Liste des groupes avec nombre de contacts
2. **Import Contact** - Import CSV/Excel dans un groupe
3. **Export Contact** - Export sélectif (Excel/CSV/TXT)

---

### 2.4 Section Reports Complète (5 onglets)

**Page:** `resources/src/views/Reports.vue`

**Onglets:**
1. **Campaign Report** - Rapports par campagne
2. **Delivery Report** - Détail de livraison par SMS
3. **Schedule Report** - Campagnes planifiées
4. **Archived Report** - Archives par période
5. **Credit History** - Historique des crédits

---

## PHASE 3 - FONCTIONNALITÉS AVANCÉES (Semaine 3-4)

### 3.1 Fallback Automatique entre Passerelles

**Objectif:** Si un provider échoue, basculer automatiquement

**Modifier:** `app/Services/SMS/SmsRouter.php`
- Ordre de fallback: airtel → moov → (autres)
- Log des tentatives
- Retourner le provider utilisé dans la réponse

---

### 3.2 Gestion STOP Automatique

**Objectif:** Ajouter automatiquement à la blacklist sur réception STOP

**Service:** `app/Services/StopWordService.php`
- Mots-clés: STOP, ARRET, UNSUB, UNSUBSCRIBE, DESABONNER
- Ajout automatique à la blacklist
- Vérification avant chaque envoi

---

### 3.3 Support International (Normalisation E.164)

**Objectif:** Supporter plusieurs pays africains

**Service:** `app/Services/PhoneNormalizationService.php`

**Pays supportés:**
| Code | Pays | Préfixe |
|------|------|---------|
| GA | Gabon | 241 |
| CM | Cameroun | 237 |
| CG | Congo | 242 |
| CI | Côte d'Ivoire | 225 |
| SN | Sénégal | 221 |

**Fonctionnalités:**
- Normalisation au format E.164 (+XXX...)
- Détection automatique du pays
- Détection de l'opérateur par préfixe

---

## STRUCTURE FICHIERS À CRÉER/MODIFIER

```
sendwave-pro/
├── app/
│   ├── Console/Commands/
│   │   └── ClosePeriodCommand.php          # NOUVEAU
│   ├── Events/
│   │   ├── BudgetAlertEvent.php            # NOUVEAU
│   │   └── BudgetExceededEvent.php         # NOUVEAU
│   ├── Models/
│   │   ├── SmsAnalytics.php                # NOUVEAU
│   │   └── PeriodClosure.php               # NOUVEAU
│   └── Services/
│       ├── AnalyticsRecordService.php      # NOUVEAU
│       ├── PeriodClosureService.php        # NOUVEAU
│       ├── BudgetService.php               # NOUVEAU
│       ├── StopWordService.php             # NOUVEAU
│       ├── PhoneNormalizationService.php   # NOUVEAU
│       └── SMS/
│           └── SmsRouter.php               # MODIFIER (fallback)
├── database/migrations/
│   ├── xxxx_create_sms_analytics_table.php
│   ├── xxxx_create_period_closures_table.php
│   └── xxxx_add_budget_to_sub_accounts.php
├── resources/src/
│   ├── views/
│   │   ├── SendSms.vue                     # REFONTE
│   │   ├── Transactional.vue               # NOUVEAU
│   │   ├── Database.vue                    # REFONTE
│   │   └── Reports.vue                     # REFONTE
│   └── components/
│       ├── sms/
│       │   ├── SendSmsForm.vue             # NOUVEAU
│       │   ├── SendOptSmsForm.vue          # NOUVEAU
│       │   ├── SmsFromFile.vue             # NOUVEAU
│       │   ├── ScheduleModal.vue           # NOUVEAU
│       │   └── TemplatesModal.vue          # NOUVEAU
│       └── ui/
│           ├── TabNav.vue                  # NOUVEAU
│           └── DataTable.vue               # AMÉLIORER
└── routes/
    └── api.php                             # AJOUTER endpoints
```

---

## CHECKLIST DE VALIDATION

### Phase 1
- [ ] Migration sms_analytics créée et exécutée
- [ ] Migration period_closures créée et exécutée
- [ ] AnalyticsRecordService fonctionne
- [ ] PeriodClosureService fonctionne
- [ ] Commande close-period testée
- [ ] Budgets par sous-compte actifs
- [ ] API keys rattachées aux sous-comptes

### Phase 2
- [ ] Interface SendSms avec 3 onglets
- [ ] Interface Transactional avec 4 onglets
- [ ] Interface Database avec 3 onglets
- [ ] Interface Reports avec 5 onglets
- [ ] Modals (Schedule, Templates) fonctionnels

### Phase 3
- [ ] Fallback automatique testé
- [ ] Gestion STOP automatique active
- [ ] Normalisation E.164 pour multi-pays
- [ ] HTTPS configuré en production

---

## COMMANDES CLAUDE CODE SUGGÉRÉES

```bash
# Phase 1 - Créer les migrations
"Crée la migration pour la table sms_analytics selon le plan"

# Phase 1 - Créer les services
"Crée le service AnalyticsRecordService selon le plan"

# Phase 2 - Refonte interface
"Modifie SendSms.vue pour avoir 3 onglets selon les maquettes"

# Phase 3 - Fallback
"Modifie SmsRouter.php pour ajouter le fallback automatique"
```

---

*Document généré le 25 Janvier 2026 pour SendWave Pro / JOBS SMS*
