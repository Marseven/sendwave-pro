# Swagger/OpenAPI Documentation

SendWave Pro utilise Swagger/OpenAPI 3.0 pour documenter toutes les API publiques.

---

## Accès à la Documentation Interactive

### En Développement

```bash
# Démarrer le serveur Laravel
php artisan serve

# Accéder à Swagger UI dans votre navigateur
http://localhost:8000/api/documentation
```

### En Production

```
https://votre-domaine.com/api/documentation
```

---

## URLs Disponibles

| URL | Description |
|-----|-------------|
| `/api/documentation` | Interface Swagger UI interactive |
| `/docs` | Spécification OpenAPI JSON |
| `/api/oauth2-callback` | Callback OAuth2 (si configuré) |

---

## Fonctionnalités Swagger

### 1. **Interface Interactive**
- Testez les endpoints directement depuis le navigateur
- Aucune installation de Postman/Insomnia requise
- Interface utilisateur moderne et intuitive

### 2. **Authentification**
- Support de Bearer Token (Laravel Sanctum)
- Bouton "Authorize" en haut à droite
- Format: `Bearer {votre_token}`

### 3. **Try it Out**
- Cliquez sur un endpoint
- Cliquez sur "Try it out"
- Remplissez les paramètres requis
- Cliquez sur "Execute"
- Voyez la réponse en temps réel

### 4. **Schémas des Modèles**
- Consultez la structure des données
- Types de champs
- Champs requis vs optionnels
- Exemples de valeurs

---

## Comment Utiliser

### Étape 1: Obtenir un Token

1. Allez sur `/api/auth/login` ou `/api/auth/register`
2. Cliquez sur "Try it out"
3. Remplissez les credentials:
   ```json
   {
     "email": "admin@sendwave.com",
     "password": "password123"
   }
   ```
4. Cliquez sur "Execute"
5. Copiez le `access_token` de la réponse

### Étape 2: Autoriser

1. Cliquez sur le bouton **"Authorize"** (en haut à droite)
2. Entrez: `Bearer {votre_token}`
   - Exemple: `Bearer 1|abcdefghijklmnopqrstuvwxyz123456`
3. Cliquez sur "Authorize"
4. Fermez la fenêtre

### Étape 3: Tester les Endpoints

Vous pouvez maintenant tester tous les endpoints protégés!

---

## Endpoints Documentés

### Authentication (Public)
- `POST /api/auth/login` - Connexion
- `POST /api/auth/register` - Inscription
- `POST /api/auth/logout` - Déconnexion *(Auth requis)*
- `GET /api/auth/me` - Utilisateur actuel *(Auth requis)*

### Contacts *(Auth requis)*
- `GET /api/contacts` - Liste des contacts
- `POST /api/contacts` - Créer un contact
- `GET /api/contacts/{id}` - Détails d'un contact
- `PUT /api/contacts/{id}` - Modifier un contact
- `DELETE /api/contacts/{id}` - Supprimer un contact
- `POST /api/contacts/import` - Importer CSV

### Messages *(Auth requis)*
- `POST /api/messages/send` - Envoyer SMS
- `POST /api/messages/analyze` - Analyser des numéros
- `GET /api/messages/history` - Historique des messages

### Et bien plus...
- Contact Groups
- Campaigns
- Templates
- Sub-Accounts
- Webhooks
- Blacklist
- Audit Logs

---

## Regénérer la Documentation

Si vous modifiez les annotations Swagger dans le code:

```bash
# Regénérer la documentation
php artisan l5-swagger:generate

# Vérifier la génération
ls -la storage/api-docs/api-docs.json
```

---

## Annotations Swagger

Les annotations OpenAPI sont placées dans les contrôleurs:

```php
/**
 * @OA\Post(
 *     path="/api/auth/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *     description="Authenticate user and return access token",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", format="password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful"
 *     )
 * )
 */
public function login(Request $request)
{
    // ...
}
```

---

## Configuration

Le fichier de configuration est `config/l5-swagger.php`.

Paramètres importants:

```php
'routes' => [
    'api' => 'api/documentation',  // URL Swagger UI
],

'paths' => [
    'docs_json' => 'api-docs.json',  // Fichier JSON
    'annotations' => [
        base_path('app'),  // Scan app/ pour annotations
    ],
],

'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),  // false en production
```

---

## Production

### Désactiver la Génération Automatique

Dans `.env` en production:

```env
L5_SWAGGER_GENERATE_ALWAYS=false
```

### Générer Avant Déploiement

```bash
# Dans votre pipeline CI/CD
php artisan l5-swagger:generate
```

### Cacher la Route (Optionnel)

Si vous voulez protéger Swagger en production:

```php
// config/l5-swagger.php
'defaults' => [
    'routes' => [
        'middleware' => [
            'api' => ['auth:sanctum'],  // Requiert authentification
        ],
    ],
],
```

---

## Dépannage

### "404 Not Found" sur /api/documentation

```bash
# Vider les caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Regénérer Swagger
php artisan l5-swagger:generate
```

### Annotations Non Détectées

Vérifiez que:
1. Les annotations commencent par `@OA\`
2. Le contrôleur est dans `app/`
3. La syntaxe PHPDoc est correcte

```bash
# Regénérer avec verbose
php artisan l5-swagger:generate --verbose
```

### Permission Denied

```bash
# Donner les permissions à storage/
chmod -R 775 storage
```

---

## Ressources

- **OpenAPI Spec**: https://swagger.io/specification/
- **L5-Swagger Package**: https://github.com/DarkaOnLine/L5-Swagger
- **Swagger UI**: https://swagger.io/tools/swagger-ui/

---

## Support

Pour toute question sur la documentation Swagger:
- Consultez `API_DOCUMENTATION.md` pour la version Markdown
- Vérifiez les annotations dans `app/Http/Controllers/`
- Contactez l'équipe de développement

---

**Version**: 2.1
**Dernière mise à jour**: 7 novembre 2025
**Package**: darkaonline/l5-swagger v9.0
