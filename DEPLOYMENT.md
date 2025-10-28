# Guide de Déploiement - JOBS SMS

## Configuration du sous-domaine

Pour avoir des URLs propres (`http://mysmscampaign.jobs-conseil.com/dashboard`), le document root de votre sous-domaine doit pointer vers le dossier `/public` du projet.

### Configuration Hostinger

1. **Accédez au panneau Hostinger**
   - Allez dans "Domaines" > "Gérer"
   - Sélectionnez `mysmscampaign.jobs-conseil.com`

2. **Modifiez le Document Root**
   - Cliquez sur "Modifier le Document Root"
   - Changez le chemin de :
     ```
     /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign
     ```
   - Vers :
     ```
     /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign/public
     ```

3. **Sauvegardez les modifications**

## Déploiement

### 1. Connexion SSH
```bash
ssh u663389624@uk-fast-web1375.main-hosting.eu
cd /home/u663389624/domains/jobs-conseil.com/public_html/mysmscampaign
```

### 2. Récupérer les dernières modifications
```bash
git pull origin main
```

### 3. Lancer le script de déploiement
```bash
bash .deploy.sh
```

## Structure des URLs

Après configuration, vos URLs seront propres :

- ✅ `http://mysmscampaign.jobs-conseil.com/dashboard`
- ✅ `http://mysmscampaign.jobs-conseil.com/contacts`
- ✅ `http://mysmscampaign.jobs-conseil.com/campaigns/history`
- ❌ ~~`http://mysmscampaign.jobs-conseil.com/sendwave-pro/public/dashboard`~~ (ancien)

## Vérification

1. Accédez à `http://mysmscampaign.jobs-conseil.com`
2. Vous devriez être redirigé vers `/dashboard` ou `/login`
3. La barre de progression apparaît en haut lors des changements de page

## En cas de problème

### URLs ne fonctionnent pas
- Vérifiez que le document root pointe bien vers `/public`
- Vérifiez que le fichier `.htaccess` existe dans `/public`
- Vérifiez que `mod_rewrite` est activé sur Apache

### Barre de progression ne s'affiche pas
- Vérifiez que les fichiers de build sont à jour
- Relancez le déploiement avec `bash .deploy.sh`

### Page blanche
- Vérifiez les logs PHP : `tail -f storage/logs/laravel.log`
- Vérifiez les permissions : `chmod -R 755 storage bootstrap/cache`
