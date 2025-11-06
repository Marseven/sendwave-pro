# 📨 Configuration des Queues SMS - SendWave Pro

## 🎯 Objectif
Éviter les erreurs 500 lors d'envois simultanés en utilisant un système de queues asynchrone.

---

## ✅ Ce qui a été implémenté

### 1. **Jobs créés**
- `SendSmsJob` : Envoi d'un SMS individuel
- `SendBulkSmsJob` : Envoi en masse (dispatche plusieurs SendSmsJob)

### 2. **Fonctionnalités**
- ✅ Retry automatique (3 tentatives) avec backoff exponentiel
- ✅ Logs détaillés de chaque tentative
- ✅ Gestion d'erreurs robuste
- ✅ Rate limiting (100 SMS/min par utilisateur)
- ✅ Queues séparées : `sms` (individuel) et `bulk-sms` (masse)

### 3. **Rate Limiting configuré**
- **100 SMS par minute** par utilisateur
- **5 campagnes par heure** maximum
- **10000 SMS par heure** en masse

---

## 🚀 Démarrage du Queue Worker

### **Sur votre machine locale (MAMP)**

```bash
# Se placer dans le répertoire du projet
cd /Applications/MAMP/htdocs/sendwave-pro

# Démarrer le worker pour traiter les jobs
php artisan queue:work --queue=bulk-sms,sms,default --tries=3 --timeout=60

# Ou en mode verbose pour voir les détails
php artisan queue:work --queue=bulk-sms,sms,default --tries=3 --timeout=60 -vvv
```

**Options expliquées:**
- `--queue=bulk-sms,sms,default` : Traite les queues par ordre de priorité
- `--tries=3` : Maximum 3 tentatives par job
- `--timeout=60` : Timeout de 60 secondes par job
- `-vvv` : Mode très verbeux (debug)

---

### **Sur Hostinger (Production)**

#### Option 1 : Via SSH (Recommandé)

```bash
# Se connecter en SSH
ssh u104701491@lightgreen-otter-916987.hostingersite.com

# Aller dans le dossier
cd public_html

# Démarrer le worker en arrière-plan
nohup php artisan queue:work --queue=bulk-sms,sms,default --tries=3 --timeout=60 > storage/logs/queue.log 2>&1 &

# Vérifier que le worker tourne
ps aux | grep "queue:work"
```

#### Option 2 : Cron job (Si pas de SSH persistant)

Ajouter dans le crontab :

```bash
* * * * * cd /home/u104701491/domains/lightgreen-otter-916987.hostingersite.com/public_html && php artisan queue:work --stop-when-empty --queue=bulk-sms,sms,default --tries=3 >> storage/logs/queue-cron.log 2>&1
```

Cette commande lance un worker toutes les minutes qui s'arrête quand il n'y a plus de jobs.

#### Option 3 : Supervisor (Le plus robuste)

Créer `/etc/supervisor/conf.d/sendwave-queue.conf` :

```ini
[program:sendwave-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/u104701491/domains/lightgreen-otter-916987.hostingersite.com/public_html/artisan queue:work --queue=bulk-sms,sms,default --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/home/u104701491/domains/lightgreen-otter-916987.hostingersite.com/public_html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Puis :
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sendwave-queue:*
```

---

## 🧪 Tester le système

### Test 1 : Envoi simple

```bash
# Terminal 1 : Démarrer le worker
php artisan queue:work -vvv

# Terminal 2 : Envoyer un SMS via l'interface ou API
# L'envoi devrait apparaître dans les logs du Terminal 1
```

### Test 2 : Envois simultanés (reproduire le bug 500)

```bash
# Script de test pour envoyer 10 SMS en même temps
# Créer test-concurrent-sms.php

<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\SendSmsJob;

// Dispatcher 10 SMS en même temps
for ($i = 0; $i < 10; $i++) {
    SendSmsJob::dispatch(
        1, // user_id
        '+241 77 75 07 37',
        "Test concurrent n°{$i}"
    )->onQueue('sms');
}

echo "✅ 10 SMS dispatchés en queue\n";
```

Exécuter :
```bash
php test-concurrent-sms.php
```

Résultat attendu : **Aucune erreur 500**, tous les SMS dans la queue.

---

## 📊 Monitoring des Queues

### Voir les jobs en attente

```bash
# Via Tinker
php artisan tinker

>>> DB::table('jobs')->count()
=> 5

>>> DB::table('jobs')->get()
```

### Voir les jobs échoués

```bash
# Lister les jobs qui ont échoué après 3 tentatives
php artisan queue:failed

# Réessayer un job échoué
php artisan queue:retry {id}

# Réessayer tous les jobs échoués
php artisan queue:retry all

# Supprimer les jobs échoués
php artisan queue:flush
```

### Logs

Les logs des jobs sont dans :
- `storage/logs/laravel.log` (logs généraux)
- `storage/logs/queue.log` (logs du worker si nohup)

Rechercher :
```bash
grep "SendSmsJob" storage/logs/laravel.log
```

---

## 🔧 Troubleshooting

### Problème : Le worker s'arrête tout seul

**Solution :**
- Utiliser Supervisor (Option 3 ci-dessus)
- Ou redémarrer automatiquement avec :
  ```bash
  while true; do php artisan queue:work --queue=bulk-sms,sms,default --tries=3 --timeout=60; sleep 5; done
  ```

### Problème : Jobs bloqués dans la queue

**Vérifier :**
```bash
# Voir les jobs en cours
php artisan queue:work --once
```

**Forcer le traitement :**
```bash
# Purger les jobs bloqués (> 1 heure)
php artisan queue:prune-batches --hours=1
```

### Problème : Erreurs 500 persistent

**Vérifier :**
1. Le worker est bien démarré : `ps aux | grep queue:work`
2. Les logs : `tail -f storage/logs/laravel.log`
3. La table `jobs` n'est pas vide : `SELECT COUNT(*) FROM jobs;`
4. La migration des jobs est bien faite : `php artisan migrate:status`

---

## 📈 Performance

### Nombre de workers recommandé

| Charge | Workers | Commande |
|--------|---------|----------|
| Faible (<100 SMS/h) | 1 | `queue:work --queue=bulk-sms,sms,default` |
| Moyenne (<1000 SMS/h) | 2-3 | Supervisor avec `numprocs=2` |
| Élevée (>1000 SMS/h) | 5+ | Supervisor avec `numprocs=5` |

### Optimisations

```bash
# Augmenter la mémoire
php -d memory_limit=256M artisan queue:work

# Traiter plusieurs jobs en parallèle (Redis requis)
php artisan queue:work --queue=bulk-sms,sms,default --sleep=3 --tries=3 --max-jobs=1000 --max-time=3600
```

---

## 🔐 Sécurité

### En production
1. ✅ Rate limiting activé (100 SMS/min)
2. ✅ Retry avec backoff exponentiel
3. ✅ Timeout de 60s par job
4. ✅ Logs détaillés
5. ⚠️ Ajouter monitoring (Sentry, New Relic, etc.)
6. ⚠️ Ajouter alertes si queue trop longue

---

## 📝 Checklist de déploiement

- [ ] Exécuter `php artisan migrate` (créer tables jobs et failed_jobs)
- [ ] Démarrer le queue worker
- [ ] Tester un envoi simple
- [ ] Tester un envoi en masse
- [ ] Vérifier les logs
- [ ] Configurer Supervisor (production)
- [ ] Ajouter cron de monitoring
- [ ] Documenter pour l'équipe

---

## 🆘 Support

En cas de problème :
1. Vérifier les logs : `storage/logs/laravel.log`
2. Vérifier les jobs échoués : `php artisan queue:failed`
3. Redémarrer le worker : `killall -9 php && php artisan queue:work`
4. Contacter le support technique

---

**Auteur:** Claude AI
**Date:** 6 Novembre 2025
**Version:** 1.0
