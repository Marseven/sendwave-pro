# Guide de Tests - SendWave Pro

## Table des matières

1. [Configuration initiale](#1-configuration-initiale)
2. [Authentification](#2-authentification)
3. [Gestion des Comptes (SuperAdmin)](#3-gestion-des-comptes-superadmin)
4. [Gestion des Utilisateurs](#4-gestion-des-utilisateurs)
5. [Rôles et Permissions](#5-rôles-et-permissions)
6. [Contacts](#6-contacts)
7. [Groupes de Contacts](#7-groupes-de-contacts)
8. [Import de Données](#8-import-de-données)
9. [Templates SMS](#9-templates-sms)
10. [Envoi SMS](#10-envoi-sms)
11. [Campagnes](#11-campagnes)
12. [Historique](#12-historique)
13. [Analytics et Rapports](#13-analytics-et-rapports)
14. [Configuration SMS](#14-configuration-sms)
15. [Clés API](#15-clés-api)
16. [Webhooks](#16-webhooks)
17. [Liste Noire](#17-liste-noire)
18. [Journal d'Audit](#18-journal-daudit)
19. [Paramètres](#19-paramètres)
20. [Tests API](#20-tests-api)

---

## 1. Configuration initiale

### Prérequis
```bash
# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sendwave_pro
DB_USERNAME=root
DB_PASSWORD=

# Exécuter les migrations et seeders
php artisan migrate:fresh --seed

# Compiler le frontend
npm run build
```

### Comptes de test créés par les seeders

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@jobs-sms.com | password123 | SuperAdmin |

---

## 2. Authentification

### 2.1 Connexion

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 2.1.1 | Connexion valide | 1. Aller sur `/login`<br>2. Entrer `admin@jobs-sms.com` / `password123`<br>3. Cliquer "Connexion" | Redirection vers Dashboard |
| 2.1.2 | Email invalide | 1. Entrer un email incorrect<br>2. Cliquer "Connexion" | Message d'erreur "Identifiants incorrects" |
| 2.1.3 | Mot de passe invalide | 1. Entrer email correct, mauvais mot de passe<br>2. Cliquer "Connexion" | Message d'erreur "Identifiants incorrects" |
| 2.1.4 | Champs vides | 1. Laisser les champs vides<br>2. Cliquer "Connexion" | Validation HTML5 bloque la soumission |

### 2.2 Déconnexion

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 2.2.1 | Déconnexion | 1. Cliquer sur le menu utilisateur (coin supérieur droit)<br>2. Cliquer "Déconnexion" | Redirection vers `/login`, token supprimé |

### 2.3 Session

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 2.3.1 | Accès protégé sans auth | 1. Se déconnecter<br>2. Accéder directement à `/dashboard` | Redirection vers `/login` |
| 2.3.2 | Persistance session | 1. Se connecter<br>2. Fermer le navigateur<br>3. Rouvrir et aller sur l'app | Session maintenue (si token valide) |

---

## 3. Gestion des Comptes (SuperAdmin)

> **Prérequis**: Être connecté en tant que SuperAdmin

### 3.1 Liste des Comptes

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 3.1.1 | Accès menu | 1. Cliquer "Gestion Comptes" dans le menu | Page Comptes affichée, onglet "Comptes" actif |
| 3.1.2 | Liste vide | Si aucun compte créé | Message "Aucun compte" affiché |

### 3.2 Création de Compte

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 3.2.1 | Créer compte valide | 1. Cliquer "Nouveau compte"<br>2. Remplir: Nom, Email, Crédits<br>3. Remplir: Admin (nom, email, mot de passe)<br>4. Cliquer "Créer" | Compte créé, apparaît dans la liste |
| 3.2.2 | Email dupliqué | 1. Créer un compte avec un email existant | Erreur "Email déjà utilisé" |
| 3.2.3 | Champs requis manquants | 1. Soumettre sans remplir les champs requis | Validation bloque la soumission |

### 3.3 Modification de Compte

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 3.3.1 | Modifier compte | 1. Cliquer "Modifier" sur un compte<br>2. Changer le nom<br>3. Sauvegarder | Nom mis à jour |
| 3.3.2 | Modifier budget | 1. Modifier le budget mensuel<br>2. Sauvegarder | Budget mis à jour |

### 3.4 Gestion des Crédits

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 3.4.1 | Ajouter crédits | 1. Cliquer "Crédits" sur un compte<br>2. Entrer montant (ex: 1000)<br>3. Cliquer "Ajouter" | Solde augmenté de 1000 |
| 3.4.2 | Montant négatif | 1. Entrer un montant négatif | Erreur de validation |

### 3.5 Suspension/Activation

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 3.5.1 | Suspendre compte | 1. Cliquer l'icône "Suspendre" (rouge) | Compte passe en "Suspendu", tous les utilisateurs suspendus |
| 3.5.2 | Activer compte | 1. Cliquer l'icône "Activer" (vert) | Compte passe en "Actif", utilisateurs réactivés |

---

## 4. Gestion des Utilisateurs

### 4.1 En tant que SuperAdmin

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 4.1.1 | Voir tous les utilisateurs | 1. Aller sur "Gestion Utilisateurs"<br>2. Onglet "Utilisateurs" | Liste de tous les utilisateurs |
| 4.1.2 | Filtrer par compte | 1. Sélectionner un compte dans le filtre | Seuls les utilisateurs du compte affichés |
| 4.1.3 | Créer utilisateur | 1. Cliquer "Nouvel utilisateur"<br>2. Remplir formulaire (nom, email, mot de passe, rôle, compte)<br>3. Sauvegarder | Utilisateur créé |
| 4.1.4 | Modifier utilisateur | 1. Cliquer "Modifier" sur un utilisateur<br>2. Changer le rôle<br>3. Sauvegarder | Rôle mis à jour |
| 4.1.5 | Suspendre utilisateur | 1. Cliquer icône "Suspendre" | Utilisateur suspendu |
| 4.1.6 | Supprimer utilisateur | 1. Cliquer icône "Supprimer"<br>2. Confirmer | Utilisateur supprimé |

### 4.2 En tant qu'Admin

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 4.2.1 | Voir ses agents | 1. Aller sur "Sous-comptes" | Seuls les agents du compte affichés |
| 4.2.2 | Créer agent | 1. Cliquer "Nouvel agent"<br>2. Remplir (seul rôle "Agent" disponible) | Agent créé |
| 4.2.3 | Ne pas voir autres comptes | 1. Vérifier l'interface | Onglet "Comptes" non visible |

### 4.3 En tant qu'Agent

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 4.3.1 | Pas d'accès gestion | 1. Vérifier le menu | Lien "Sous-comptes" non visible |
| 4.3.2 | Accès direct bloqué | 1. Aller directement sur `/accounts` | Erreur 403 ou redirection |

---

## 5. Rôles et Permissions

### 5.1 Rôles Système (lecture seule)

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 5.1.1 | Voir rôles système | 1. Onglet "Rôles système" | 3 rôles affichés: SuperAdmin, Admin, Agent |
| 5.1.2 | Non modifiable | 1. Vérifier l'interface | Pas de bouton modifier/supprimer |

### 5.2 Rôles Personnalisés (SuperAdmin)

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 5.2.1 | Créer rôle | 1. Onglet "Rôles personnalisés"<br>2. Cliquer "Nouveau rôle"<br>3. Entrer nom, description<br>4. Sélectionner permissions<br>5. Sauvegarder | Rôle créé |
| 5.2.2 | Modifier rôle | 1. Cliquer "Modifier"<br>2. Changer permissions<br>3. Sauvegarder | Rôle mis à jour |
| 5.2.3 | Dupliquer rôle | 1. Cliquer "Dupliquer" | Copie créée avec nom "Copie de..." |
| 5.2.4 | Supprimer rôle non utilisé | 1. Cliquer "Supprimer" sur rôle sans utilisateurs | Rôle supprimé |
| 5.2.5 | Supprimer rôle utilisé | 1. Cliquer "Supprimer" sur rôle avec utilisateurs | Erreur, suppression bloquée |

### 5.3 Permissions (lecture seule)

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 5.3.1 | Voir permissions | 1. Onglet "Permissions" | Toutes les permissions listées par catégorie |

---

## 6. Contacts

### 6.1 Liste des Contacts

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 6.1.1 | Voir contacts | 1. Menu "Contacts" | Liste des contacts avec pagination |
| 6.1.2 | Rechercher | 1. Taper dans le champ recherche | Filtrage en temps réel |
| 6.1.3 | Pagination | 1. Naviguer entre les pages | Contacts affichés correctement |

### 6.2 Création de Contact

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 6.2.1 | Créer contact | 1. Cliquer "Nouveau contact"<br>2. Remplir: Prénom, Nom, Téléphone<br>3. Sauvegarder | Contact créé |
| 6.2.2 | Téléphone invalide | 1. Entrer un format invalide | Erreur de validation |
| 6.2.3 | Téléphone dupliqué | 1. Créer contact avec numéro existant | Erreur "Numéro déjà utilisé" |

### 6.3 Modification/Suppression

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 6.3.1 | Modifier contact | 1. Cliquer "Modifier"<br>2. Changer nom<br>3. Sauvegarder | Contact mis à jour |
| 6.3.2 | Supprimer contact | 1. Cliquer "Supprimer"<br>2. Confirmer | Contact supprimé |
| 6.3.3 | Suppression multiple | 1. Sélectionner plusieurs contacts<br>2. Cliquer "Supprimer sélection" | Contacts supprimés |

---

## 7. Groupes de Contacts

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 7.1 | Créer groupe | 1. Menu "Base de données" ou icône groupes<br>2. Cliquer "Nouveau groupe"<br>3. Entrer nom, description | Groupe créé |
| 7.2 | Ajouter contacts au groupe | 1. Ouvrir un groupe<br>2. Cliquer "Ajouter contacts"<br>3. Sélectionner contacts | Contacts ajoutés |
| 7.3 | Retirer contact du groupe | 1. Dans un groupe, cliquer "Retirer" sur un contact | Contact retiré du groupe |
| 7.4 | Supprimer groupe | 1. Cliquer "Supprimer" sur le groupe<br>2. Confirmer | Groupe supprimé (contacts non supprimés) |

---

## 8. Import de Données

### 8.1 Import CSV

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 8.1.1 | Import CSV valide | 1. Contacts > "Importer"<br>2. Sélectionner fichier CSV<br>3. Mapper les colonnes<br>4. Prévisualiser<br>5. Importer | Contacts importés, résumé affiché |
| 8.1.2 | Gestion doublons - Ignorer | 1. Importer avec option "Ignorer les doublons" | Doublons non importés |
| 8.1.3 | Gestion doublons - Mettre à jour | 1. Importer avec option "Mettre à jour" | Contacts existants mis à jour |
| 8.1.4 | Fichier invalide | 1. Importer un fichier non-CSV | Erreur de format |

### 8.2 Import Excel

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 8.2.1 | Import XLSX | 1. Sélectionner fichier .xlsx<br>2. Suivre les étapes | Import réussi |
| 8.2.2 | Import XLS | 1. Sélectionner fichier .xls | Import réussi |

### Fichier de test CSV
```csv
prenom,nom,telephone,email
Jean,Dupont,+22990123456,jean@example.com
Marie,Martin,+22991234567,marie@example.com
```

---

## 9. Templates SMS

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 9.1 | Créer template | 1. Menu "Modèles"<br>2. Cliquer "Nouveau modèle"<br>3. Entrer nom et contenu<br>4. Sauvegarder | Template créé |
| 9.2 | Variables | 1. Créer template avec `{prenom}`, `{nom}` | Variables acceptées |
| 9.3 | Prévisualiser | 1. Cliquer "Prévisualiser" sur un template | Aperçu avec variables remplacées |
| 9.4 | Modifier template | 1. Cliquer "Modifier"<br>2. Changer contenu | Template mis à jour |
| 9.5 | Supprimer template | 1. Cliquer "Supprimer"<br>2. Confirmer | Template supprimé |
| 9.6 | Compteur caractères | 1. Taper dans le champ message | Compteur affiche nombre de caractères et SMS |

---

## 10. Envoi SMS

### 10.1 Envoi Simple

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 10.1.1 | Envoi à un numéro | 1. Menu "Envoyer SMS"<br>2. Entrer numéro destinataire<br>3. Écrire message<br>4. Cliquer "Envoyer" | SMS envoyé, confirmation affichée |
| 10.1.2 | Envoi à plusieurs numéros | 1. Entrer plusieurs numéros (séparés par virgule ou retour ligne)<br>2. Envoyer | SMS envoyés à tous |
| 10.1.3 | Sélection contacts | 1. Cliquer "Sélectionner contacts"<br>2. Choisir des contacts<br>3. Envoyer | SMS envoyés aux contacts sélectionnés |

### 10.2 Envoi avec Template

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 10.2.1 | Utiliser template | 1. Sélectionner un template<br>2. Envoyer | Message du template utilisé |
| 10.2.2 | Variables remplacées | 1. Template avec `{prenom}`<br>2. Envoyer à un contact | Variable remplacée par le prénom |

### 10.3 Validations

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 10.3.1 | Numéro invalide | 1. Entrer numéro invalide<br>2. Envoyer | Erreur de validation |
| 10.3.2 | Message vide | 1. Laisser message vide<br>2. Envoyer | Erreur "Message requis" |
| 10.3.3 | Crédits insuffisants | 1. Envoyer avec 0 crédits | Erreur "Crédits insuffisants" |

---

## 11. Campagnes

### 11.1 Création de Campagne

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 11.1.1 | Créer campagne | 1. Menu "Nouvelle campagne"<br>2. Entrer nom<br>3. Sélectionner destinataires (groupe ou contacts)<br>4. Écrire message<br>5. Sauvegarder | Campagne créée en brouillon |
| 11.1.2 | Campagne avec template | 1. Sélectionner un template lors de la création | Message pré-rempli |

### 11.2 Planification

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 11.2.1 | Planifier envoi | 1. Créer campagne<br>2. Cocher "Planifier"<br>3. Choisir date/heure future | Campagne planifiée |
| 11.2.2 | Modifier planification | 1. Modifier date de planification | Date mise à jour |
| 11.2.3 | Annuler planification | 1. Supprimer la planification | Campagne revient en brouillon |

### 11.3 Envoi de Campagne

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 11.3.1 | Envoyer maintenant | 1. Cliquer "Envoyer" sur une campagne<br>2. Confirmer | Campagne envoyée |
| 11.3.2 | Cloner campagne | 1. Cliquer "Cloner" | Copie créée en brouillon |

### 11.4 Calendrier

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 11.4.1 | Voir calendrier | 1. Menu "Calendrier" | Calendrier avec campagnes planifiées |
| 11.4.2 | Cliquer sur campagne | 1. Cliquer sur une campagne dans le calendrier | Détails affichés |

---

## 12. Historique

### 12.1 Historique des Campagnes

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 12.1.1 | Voir historique | 1. Menu "Historique campagnes" | Liste des campagnes avec statuts |
| 12.1.2 | Filtrer par statut | 1. Sélectionner un statut dans le filtre | Campagnes filtrées |
| 12.1.3 | Filtrer par date | 1. Sélectionner une plage de dates | Campagnes de la période |
| 12.1.4 | Voir détails | 1. Cliquer sur une campagne | Statistiques détaillées |

### 12.2 Historique des Messages

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 12.2.1 | Voir historique | 1. Menu "Historique messages" | Liste des SMS envoyés |
| 12.2.2 | Rechercher par numéro | 1. Entrer un numéro dans la recherche | Messages filtrés |
| 12.2.3 | Filtrer par statut | 1. Filtrer par "Livré", "Échoué", etc. | Messages filtrés |
| 12.2.4 | Exporter | 1. Cliquer "Exporter"<br>2. Choisir format (CSV/Excel) | Fichier téléchargé |

---

## 13. Analytics et Rapports

### 13.1 Dashboard

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 13.1.1 | Voir dashboard | 1. Menu "Tableau de bord" | Statistiques globales affichées |
| 13.1.2 | Graphiques | Vérifier présence des graphiques | Graphiques d'envoi, taux de livraison |
| 13.1.3 | Changer période | 1. Sélectionner "7 jours", "30 jours", etc. | Données mises à jour |

### 13.2 Rapports

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 13.2.1 | Voir rapports | 1. Menu "Rapports" | Rapports détaillés |
| 13.2.2 | Exporter PDF | 1. Cliquer "Exporter PDF" | PDF téléchargé |
| 13.2.3 | Exporter Excel | 1. Cliquer "Exporter Excel" | Excel téléchargé |

---

## 14. Configuration SMS

> **Prérequis**: Être Admin ou SuperAdmin

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 14.1 | Voir opérateurs | 1. Menu "Config. Opérateurs" | Liste des opérateurs disponibles |
| 14.2 | Configurer opérateur | 1. Cliquer sur un opérateur<br>2. Entrer les credentials API<br>3. Sauvegarder | Configuration sauvegardée |
| 14.3 | Tester connexion | 1. Cliquer "Tester" | Résultat du test affiché |
| 14.4 | Activer/Désactiver | 1. Basculer le switch d'activation | Opérateur activé/désactivé |

---

## 15. Clés API

> **Prérequis**: Avoir la permission `manage_api_keys`

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 15.1 | Créer clé | 1. Menu "Clés API"<br>2. Cliquer "Nouvelle clé"<br>3. Entrer nom<br>4. Sauvegarder | Clé générée et affichée |
| 15.2 | Copier clé | 1. Cliquer "Copier" | Clé copiée dans le presse-papier |
| 15.3 | Révoquer clé | 1. Cliquer "Révoquer"<br>2. Confirmer | Clé révoquée, inutilisable |
| 15.4 | Régénérer clé | 1. Cliquer "Régénérer" | Nouvelle clé générée |
| 15.5 | Supprimer clé | 1. Cliquer "Supprimer" | Clé supprimée |

---

## 16. Webhooks

> **Prérequis**: Avoir la permission `manage_webhooks`

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 16.1 | Créer webhook | 1. Menu "Webhooks"<br>2. Cliquer "Nouveau webhook"<br>3. Entrer URL et événements<br>4. Sauvegarder | Webhook créé |
| 16.2 | Tester webhook | 1. Cliquer "Tester" | Requête envoyée, résultat affiché |
| 16.3 | Voir logs | 1. Cliquer "Logs" | Historique des appels |
| 16.4 | Activer/Désactiver | 1. Basculer le switch | Webhook activé/désactivé |
| 16.5 | Modifier webhook | 1. Cliquer "Modifier"<br>2. Changer URL<br>3. Sauvegarder | Webhook mis à jour |
| 16.6 | Supprimer webhook | 1. Cliquer "Supprimer" | Webhook supprimé |

---

## 17. Liste Noire

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 17.1 | Voir liste noire | 1. Menu "Liste noire" | Numéros bloqués affichés |
| 17.2 | Ajouter numéro | 1. Cliquer "Ajouter"<br>2. Entrer numéro et raison<br>3. Sauvegarder | Numéro ajouté |
| 17.3 | Retirer numéro | 1. Cliquer "Supprimer" sur un numéro | Numéro retiré |
| 17.4 | Envoi bloqué | 1. Essayer d'envoyer SMS à un numéro blacklisté | Envoi bloqué avec message |

---

## 18. Journal d'Audit

> **Prérequis**: Avoir la permission `view_audit_logs`

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 18.1 | Voir logs | 1. Menu "Journal d'audit" | Liste des actions |
| 18.2 | Filtrer par action | 1. Sélectionner un type d'action | Logs filtrés |
| 18.3 | Filtrer par utilisateur | 1. Sélectionner un utilisateur | Logs de cet utilisateur |
| 18.4 | Voir détails | 1. Cliquer sur une entrée | Détails de l'action |

---

## 19. Paramètres

> **Prérequis**: Avoir la permission `manage_settings`

| # | Test | Étapes | Résultat attendu |
|---|------|--------|------------------|
| 19.1 | Modifier profil | 1. Menu "Paramètres" ou "Profil"<br>2. Modifier nom, email<br>3. Sauvegarder | Profil mis à jour |
| 19.2 | Changer mot de passe | 1. Section mot de passe<br>2. Entrer ancien et nouveau mot de passe<br>3. Sauvegarder | Mot de passe changé |
| 19.3 | Notifications | 1. Section notifications<br>2. Activer/désactiver options | Préférences sauvegardées |

---

## 20. Tests API

### 20.1 Authentification API

```bash
# Login
curl -X POST http://localhost:8888/sendwave-pro/public/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@jobs-sms.com","password":"password123"}'

# Réponse attendue: { "access_token": "...", "user": {...} }
```

### 20.2 Envoi SMS via API

```bash
# Envoyer SMS
curl -X POST http://localhost:8888/sendwave-pro/public/api/messages/send \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "recipients": ["+22990123456"],
    "message": "Test API SMS"
  }'
```

### 20.3 Contacts API

```bash
# Lister contacts
curl -X GET http://localhost:8888/sendwave-pro/public/api/contacts \
  -H "Authorization: Bearer {TOKEN}"

# Créer contact
curl -X POST http://localhost:8888/sendwave-pro/public/api/contacts \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Jean",
    "last_name": "Dupont",
    "phone": "+22990123456"
  }'
```

### 20.4 Campagnes API

```bash
# Lister campagnes
curl -X GET http://localhost:8888/sendwave-pro/public/api/campaigns \
  -H "Authorization: Bearer {TOKEN}"

# Créer campagne
curl -X POST http://localhost:8888/sendwave-pro/public/api/campaigns \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ma campagne test",
    "message": "Bonjour {prenom}!",
    "group_id": 1
  }'
```

---

## Checklist Globale

### Authentification
- [ ] Connexion valide
- [ ] Connexion invalide
- [ ] Déconnexion
- [ ] Protection des routes

### RBAC
- [ ] SuperAdmin voit tout
- [ ] Admin voit son compte
- [ ] Agent limité
- [ ] Rôles personnalisés fonctionnent

### Contacts
- [ ] CRUD contacts
- [ ] Import CSV
- [ ] Import Excel
- [ ] Gestion doublons
- [ ] Groupes de contacts

### SMS
- [ ] Envoi simple
- [ ] Envoi multiple
- [ ] Templates avec variables
- [ ] Validation numéros
- [ ] Vérification crédits

### Campagnes
- [ ] Création
- [ ] Planification
- [ ] Envoi
- [ ] Historique

### Administration
- [ ] Gestion comptes
- [ ] Gestion utilisateurs
- [ ] Configuration SMS
- [ ] Clés API
- [ ] Webhooks
- [ ] Liste noire
- [ ] Journal d'audit

---

## Notes

- **Environnement de test**: Utilisez une base de données séparée pour les tests
- **Données de test**: Le seeder crée un utilisateur SuperAdmin
- **SMS de test**: Configurez un opérateur de test ou utilisez le mode sandbox
- **Crédits**: Assurez-vous d'avoir des crédits suffisants pour les tests d'envoi

---

*Dernière mise à jour: Janvier 2026*
