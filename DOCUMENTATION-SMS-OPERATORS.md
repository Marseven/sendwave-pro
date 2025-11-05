# Documentation SMS - Opérateurs Gabonais

## Vue d'ensemble

Le système SMS utilise maintenant une architecture basée sur le routage automatique par opérateur. Les messages sont envoyés via l'API de l'opérateur correspondant (Airtel ou Moov) en fonction du préfixe du numéro de téléphone.

## Préfixes des opérateurs au Gabon

### Airtel Gabon
- **Préfixes** : 77, 74, 76
- **Exemples** : +241 77 75 07 37, 24174123456, 076234567

### Moov Gabon
- **Préfixes** : 60, 62, 65, 66
- **Exemples** : +241 62 34 56 78, 24160123456, 065987654

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    MessageController                         │
│               (Reçoit les requêtes API)                     │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                      SmsRouter                               │
│        (Routage automatique par opérateur)                  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │        OperatorDetector                              │  │
│  │  (Détection de l'opérateur par préfixe)             │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────┬───────────────────────┘
               │                      │
               ▼                      ▼
    ┌──────────────────┐   ┌──────────────────┐
    │  AirtelService   │   │   MoovService    │
    │                  │   │                  │
    │ API Airtel GA    │   │ API Moov GA      │
    └──────────────────┘   └──────────────────┘
```

## Configuration

### 1. Variables d'environnement

Ajouter dans le fichier `.env` :

```env
# Airtel Gabon
AIRTEL_API_URL=https://messaging.airtel.ga:9002/smshttp/qs/
AIRTEL_USERNAME=votre_username
AIRTEL_PASSWORD=votre_password
AIRTEL_ORIGIN_ADDR=VOTRESENDER
AIRTEL_ENABLED=true

# Moov Gabon (à configurer plus tard)
MOOV_ENABLED=false
```

### 2. Test de la configuration

```bash
# Vider le cache de configuration
php artisan config:clear

# Vérifier que la config est chargée
php artisan tinker
>>> config('sms.airtel')
```

## Utilisation de l'API

### 1. Envoyer un SMS simple

**Endpoint** : `POST /api/messages/send`

**Request** :
```json
{
  "recipients": ["+241 77 75 07 37"],
  "message": "Bonjour, votre inscription JOBS SMS a été validée."
}
```

**Response (succès)** :
```json
{
  "message": "Message envoyé avec succès",
  "data": {
    "provider": "airtel",
    "phone": "24177750737",
    "sms_count": 1
  }
}
```

### 2. Envoyer des SMS en masse

**Request** :
```json
{
  "recipients": [
    "+241 77 75 07 37",
    "+241 62 34 56 78",
    "+241 74 12 34 56",
    "+241 60 11 22 33"
  ],
  "message": "Votre message ici"
}
```

**Response** :
```json
{
  "message": "Envoi terminé",
  "data": {
    "total": 4,
    "sent": 4,
    "failed": 0,
    "sms_count": 1,
    "by_operator": {
      "airtel": 2,
      "moov": 2,
      "unknown": 0
    },
    "details": [
      {
        "success": true,
        "provider": "airtel",
        "phone": "24177750737"
      },
      ...
    ]
  }
}
```

### 3. Analyser des numéros de téléphone

**Endpoint** : `POST /api/messages/analyze`

**Request** :
```json
{
  "phone_numbers": [
    "+241 77 75 07 37",
    "+241 62 34 56 78",
    "24174123456"
  ]
}
```

**Response** :
```json
{
  "message": "Analyse effectuée",
  "data": {
    "total": 3,
    "airtel_count": 2,
    "moov_count": 1,
    "unknown_count": 0,
    "airtel_enabled": true,
    "moov_enabled": false,
    "grouped": {
      "airtel": ["+241 77 75 07 37", "24174123456"],
      "moov": ["+241 62 34 56 78"],
      "unknown": []
    }
  }
}
```

### 4. Obtenir les infos d'un numéro

**Endpoint** : `POST /api/messages/number-info`

**Request** :
```json
{
  "phone_number": "+241 77 75 07 37"
}
```

**Response** :
```json
{
  "message": "Informations du numéro",
  "data": {
    "original": "+241 77 75 07 37",
    "cleaned": "77750737",
    "prefix": "77",
    "operator": "airtel",
    "country_code": "241",
    "full_number": "24177750737",
    "formatted": "+241 77 75 07 37"
  }
}
```

## Gestion des erreurs

### API Airtel désactivée
```json
{
  "message": "Échec de l'envoi",
  "error": "L'API Airtel est désactivée"
}
```

### API Moov non configurée
```json
{
  "message": "Échec de l'envoi",
  "error": "L'API Moov n'est pas encore configurée"
}
```

### Opérateur inconnu
```json
{
  "message": "Échec de l'envoi",
  "error": "Opérateur non reconnu pour ce numéro",
  "operator_info": {
    "prefix": "99",
    "operator": "unknown"
  }
}
```

## Formats de numéros acceptés

Le système accepte tous ces formats :

- `+241 77 75 07 37` (format international avec espaces)
- `+24177750737` (format international sans espaces)
- `24177750737` (sans le +)
- `077750737` (avec le 0 initial)
- `77750737` (juste le numéro local)

## Logs

Tous les envois sont loggés dans `storage/logs/laravel.log` :

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log
```

**Exemple de log** :
```
[2025-01-05 10:30:15] local.INFO: SMS Router - Envoi {"phone":"+241 77 75 07 37","operator":"airtel"}
[2025-01-05 10:30:15] local.INFO: Airtel SMS - Envoi {"phone":"24177750737","message":"Bonjour..."}
[2025-01-05 10:30:16] local.INFO: Airtel SMS - Succès {"phone":"24177750737","response":"..."}
```

## Campagnes SMS

Lors de l'envoi de campagnes, le système :

1. **Analyse les numéros** : Groupe automatiquement par opérateur
2. **Envoie en parallèle** : Un envoi par opérateur
3. **Retourne les statistiques** : Nombre d'envois par opérateur
4. **Log tout** : Succès et échecs détaillés

**Exemple avec 100 numéros** :
- 60 numéros Airtel → Envoyés via API Airtel
- 35 numéros Moov → En attente (API non configurée)
- 5 numéros inconnus → Échec immédiat

## Développement futur

### Ajouter l'API Moov

1. Créer le service `MoovService.php` avec les bons paramètres API
2. Ajouter les variables d'environnement dans `.env`
3. Activer : `MOOV_ENABLED=true`
4. Tester avec des numéros Moov

### Ajouter un nouvel opérateur

1. Créer `app/Services/SMS/Operators/NouvelOperateurService.php`
2. Ajouter les préfixes dans `OperatorDetector.php`
3. Ajouter la configuration dans `config/sms.php`
4. Mettre à jour `SmsRouter.php`

## Tests

### Test Airtel (une fois configuré)

```bash
curl -X POST https://votre-domaine.com/api/messages/send \
  -H "Authorization: Bearer votre_token" \
  -H "Content-Type: application/json" \
  -d '{
    "recipients": ["+241 77 75 07 37"],
    "message": "Test SMS Airtel"
  }'
```

### Test d'analyse

```bash
curl -X POST https://votre-domaine.com/api/messages/analyze \
  -H "Authorization: Bearer votre_token" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_numbers": ["+241 77 75 07 37", "+241 62 34 56 78"]
  }'
```

## Sécurité

- ✅ Toutes les routes nécessitent l'authentification Sanctum
- ✅ Les credentials API sont stockés dans `.env` (jamais en base)
- ✅ Les logs ne contiennent pas les credentials
- ✅ Validation stricte des formats de numéros
- ✅ Limite de 320 caractères par message

## Support

Pour toute question :
- Consulter les logs : `storage/logs/laravel.log`
- Vérifier la config : `php artisan config:show sms`
- Tester les routes : Collection Postman/Insomnia
