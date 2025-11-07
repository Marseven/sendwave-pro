# SendWave Pro - Deployment Guide for Hostinger

**Version**: 2.1
**Target Platform**: Hostinger Shared/VPS Hosting
**Framework**: Laravel 12 + Vue 3

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Pre-Deployment Checklist](#pre-deployment-checklist)
4. [Deployment Steps](#deployment-steps)
5. [Environment Configuration](#environment-configuration)
6. [Database Setup](#database-setup)
7. [File Permissions](#file-permissions)
8. [Cron Jobs](#cron-jobs)
9. [SSL Certificate](#ssl-certificate)
10. [Post-Deployment](#post-deployment)
11. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Local Development
- ✅ All 27 migrations run successfully
- ✅ Frontend built (`npm run build`)
- ✅ .env configured for production
- ✅ Git repository ready

### Hostinger Account
- Hosting plan with SSH access (VPS or Business plan recommended)
- MySQL database created
- Domain name configured
- SSL certificate available (free Let's Encrypt)

---

## Server Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3+
- **Composer**: 2.x
- **Node.js**: 18.x or higher (for builds)
- **Memory**: 512MB minimum (1GB recommended)
- **Disk Space**: 500MB minimum

### Required PHP Extensions
```bash
php -m | grep -E 'mbstring|xml|pdo|openssl|json|tokenizer|bcmath|ctype|fileinfo|curl'
```

Required extensions:
- ✅ BCMath
- ✅ Ctype
- ✅ cURL
- ✅ Fileinfo
- ✅ JSON
- ✅ Mbstring
- ✅ OpenSSL
- ✅ PDO
- ✅ Tokenizer
- ✅ XML

---

## Pre-Deployment Checklist

### 1. Build Frontend
```bash
cd resources
npm install
npm run build
```

Verify build output in `public/build/`

### 2. Optimize Laravel
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Test Production Build Locally
```bash
php artisan serve --env=production
```

Visit `http://localhost:8000` and test:
- ✅ Login works
- ✅ Dashboard loads
- ✅ API endpoints respond
- ✅ Frontend assets load

### 4. Database Backup
```bash
php artisan db:backup  # If you have backup package
# OR
mysqldump -u root -p sendwave_pro > backup_$(date +%Y%m%d).sql
```

---

## Deployment Steps

### Option 1: Manual Deployment via FTP/SFTP

#### Step 1: Upload Files
Upload these directories to `public_html` or your domain folder:
```
app/
bootstrap/
config/
database/
public/
resources/  (optional, only if rebuilding on server)
routes/
storage/
vendor/
.env
artisan
composer.json
composer.lock
```

**DO NOT UPLOAD**:
- `.env.example`
- `node_modules/`
- `tests/`
- `.git/`

#### Step 2: Set Document Root
In Hostinger Control Panel:
1. Go to **Advanced** → **PHP Configuration**
2. Set **Document Root** to: `public_html/public` or `/public`

### Option 2: Git Deployment (Recommended)

#### Step 1: SSH into Server
```bash
ssh username@your-domain.com
```

#### Step 2: Clone Repository
```bash
cd public_html
git clone https://github.com/yourusername/sendwave-pro.git .
# OR if already exists
git pull origin main
```

#### Step 3: Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

#### Step 4: Build Frontend (if needed)
```bash
cd resources
npm install
npm run build
cd ..
```

---

## Environment Configuration

### Create .env File
```bash
cp .env.example .env
nano .env  # or vi .env
```

### Production .env Configuration
```env
APP_NAME="SendWave Pro"
APP_ENV=production
APP_KEY=  # Generate below
APP_DEBUG=false  # IMPORTANT: Set to false!
APP_TIMEZONE=Africa/Libreville
APP_URL=https://yourdomain.com

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost  # Or your MySQL host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_strong_password

# Queue Configuration (Use database for shared hosting)
QUEUE_CONNECTION=database

# Cache Configuration
CACHE_STORE=file  # Or redis if available
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Mail Configuration (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Airtel SMS Configuration
AIRTEL_CLIENT_ID=your_airtel_client_id
AIRTEL_CLIENT_SECRET=your_airtel_client_secret
AIRTEL_SENDER_ID=SENDWAVE

# Moov SMS Configuration
MOOV_API_KEY=your_moov_api_key
MOOV_SENDER_ID=SENDWAVE

# Redis (Optional - if available on VPS)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
```

### Generate Application Key
```bash
php artisan key:generate
```

This will update `APP_KEY` in your .env file

---

## Database Setup

### Step 1: Create MySQL Database

**Via Hostinger Control Panel**:
1. Go to **Databases** → **MySQL Databases**
2. Click **Create Database**
3. Database name: `sendwave_pro`
4. Create user and grant all privileges

**Via SSH** (if you have access):
```bash
mysql -u root -p
```

```sql
CREATE DATABASE sendwave_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sendwave_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON sendwave_pro.* TO 'sendwave_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 2: Run Migrations
```bash
php artisan migrate --force
```

**Expected Output**: 27 migrations should run successfully

### Step 3: Seed Initial Data (Optional)
```bash
php artisan db:seed  # If you have seeders
```

### Step 4: Verify Database
```bash
php artisan migrate:status
```

All migrations should show `[Ran]` status

---

## File Permissions

### Set Correct Permissions
```bash
# Make storage and cache writable
chmod -R 775 storage bootstrap/cache

# Set ownership (replace with your username)
chown -R username:username storage bootstrap/cache

# Ensure storage structure exists
php artisan storage:link
```

### Directory Structure
Ensure these directories exist and are writable:
```
storage/
├── app/
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
```

### Test Permissions
```bash
# Test writing to log
php artisan tinker
>>> \Log::info('Test log entry');
>>> exit

# Check if log was created
tail -f storage/logs/laravel.log
```

---

## Cron Jobs

### Setup Laravel Scheduler

**Via Hostinger Control Panel**:
1. Go to **Advanced** → **Cron Jobs**
2. Click **Create Cron Job**
3. Configure:
   - **Type**: Custom
   - **Command**: `cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1`
   - **Frequency**: Every minute (`* * * * *`)

**Via SSH** (crontab):
```bash
crontab -e
```

Add this line:
```bash
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks

The Laravel scheduler will automatically handle:
- ✅ Recurring campaign execution
- ✅ Webhook retries
- ✅ Cache cleanup
- ✅ Session cleanup
- ✅ Failed job cleanup

### Verify Cron is Running
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Or create a test scheduled task in app/Console/Kernel.php
```

---

## SSL Certificate

### Enable Free SSL (Let's Encrypt)

**Via Hostinger Control Panel**:
1. Go to **SSL** section
2. Click **Install Free SSL Certificate**
3. Select your domain
4. Click **Install**

**Verify SSL**:
```bash
curl -I https://yourdomain.com
```

### Force HTTPS

Add to `.env`:
```env
APP_URL=https://yourdomain.com
```

Update `app/Providers/AppServiceProvider.php`:
```php
public function boot(): void
{
    if ($this->app->environment('production')) {
        \URL::forceScheme('https');
    }
}
```

---

## Post-Deployment

### 1. Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 2. Recache for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Test Application

**API Health Check**:
```bash
curl https://yourdomain.com/api/webhooks/events
```

**Frontend Check**:
Visit `https://yourdomain.com` in browser

### 4. Create First Admin User

**Via Tinker**:
```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourdomain.com',
    'password' => \Hash::make('secure_password_here'),
    'email_verified_at' => now(),
]);

echo "User created with ID: " . $user->id;
exit
```

**Via Registration**:
Visit `https://yourdomain.com/register`

### 5. Test Core Features

✅ Login/Logout
✅ Create contact
✅ Send test SMS
✅ Create campaign
✅ Create webhook
✅ Test webhook delivery
✅ Check audit logs

---

## Troubleshooting

### Issue: 500 Internal Server Error

**Solution**:
```bash
# Enable debug temporarily
# In .env: APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log

# Check permissions
chmod -R 775 storage bootstrap/cache
```

### Issue: Database Connection Failed

**Solution**:
```bash
# Test MySQL connection
mysql -u your_user -p -h localhost your_database

# Verify .env settings
cat .env | grep DB_

# Test from artisan
php artisan migrate:status
```

### Issue: Frontend Assets Not Loading

**Solution**:
```bash
# Rebuild frontend
cd resources
npm run build

# Create symlink
php artisan storage:link

# Check public/build/ exists
ls -la public/build/
```

### Issue: Webhooks Not Triggering

**Solution**:
```bash
# Check queue is running
php artisan queue:work --tries=3

# Check cron is configured
crontab -l

# Test webhook manually
php artisan tinker
>>> $webhook = \App\Models\Webhook::first();
>>> app(\App\Services\WebhookService::class)->test($webhook);
```

### Issue: SMS Not Sending

**Solution**:
```bash
# Verify API credentials in .env
cat .env | grep -E 'AIRTEL|MOOV'

# Test operator detection
php artisan tinker
>>> \App\Services\SMS\OperatorDetector::getInfo('+24162000000');

# Check logs
tail -f storage/logs/laravel.log | grep SMS
```

### Issue: Slow Performance

**Solution**:
```bash
# Enable OpCache (check with hosting provider)
# Enable query caching

# Optimize autoloader
composer dump-autoload --optimize

# Cache everything
php artisan optimize

# Consider upgrading to VPS for better performance
```

---

## Monitoring & Maintenance

### Daily Checks
- Monitor storage/logs/laravel.log for errors
- Check disk space: `df -h`
- Monitor database size
- Review webhook delivery success rates

### Weekly Tasks
- Review audit logs for suspicious activity
- Check failed jobs: `php artisan queue:failed`
- Backup database
- Monitor SMS credit usage

### Monthly Tasks
- Review and clean old logs
- Update dependencies: `composer update`
- Check for Laravel security updates
- Review and optimize database indexes

---

## Performance Optimization

### Enable OpCache (PHP 8.2+)

Ask Hostinger support to enable or check:
```bash
php -i | grep opcache
```

### Use Redis (VPS Only)
```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Enable Gzip Compression

Add to `.htaccess` in `public/`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### Database Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_messages_user_status ON messages(user_id, status);
CREATE INDEX idx_webhooks_user_active ON webhooks(user_id, is_active);
CREATE INDEX idx_contacts_user_status ON contacts(user_id, status);
```

---

## Backup Strategy

### Automated Database Backup Script

Create `backup.sh`:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/username/backups"
DB_NAME="sendwave_pro"
DB_USER="sendwave_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: db_$DATE.sql.gz"
```

Make executable:
```bash
chmod +x backup.sh
```

Add to crontab (daily at 2 AM):
```bash
0 2 * * * /home/username/backup.sh
```

---

## Security Checklist

- ✅ `APP_DEBUG=false` in production
- ✅ Strong `APP_KEY` generated
- ✅ Database credentials are strong
- ✅ SSL certificate installed and forced
- ✅ File permissions set correctly (not 777)
- ✅ `.env` file is not publicly accessible
- ✅ CORS configured properly
- ✅ Rate limiting enabled on API endpoints
- ✅ Webhook signatures verified
- ✅ CSRF protection enabled
- ✅ SQL injection prevention (using Eloquent ORM)
- ✅ XSS protection (Vue auto-escapes)

---

## Support & Resources

### Laravel Resources
- Documentation: https://laravel.com/docs/12.x
- Security: https://laravel.com/docs/12.x/security

### Hostinger Resources
- Knowledge Base: https://support.hostinger.com
- SSH Access: https://support.hostinger.com/en/articles/1583245-how-to-connect-to-your-account-using-ssh
- Cron Jobs: https://support.hostinger.com/en/articles/1583229-how-to-set-up-a-cron-job

### SendWave Pro
- API Documentation: `/API_DOCUMENTATION.md`
- Implementation Summary: `/IMPLEMENTATION_SUMMARY.md`

---

**Deployment Guide Version**: 1.0
**Last Updated**: November 7, 2025
**Next Review**: Every major version update

**Good luck with your deployment! 🚀**
