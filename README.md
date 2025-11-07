# SendWave Pro

**Enterprise SMS Campaign Management Platform**

Version 2.1 | Built with Laravel 12 + Vue 3 + TypeScript

---

## Overview

SendWave Pro is a comprehensive SMS campaign management platform designed specifically for the Gabon market, supporting both Airtel and Moov operators with automatic routing and operator detection.

### Key Features

- 📱 **Multi-Provider SMS** - Airtel & Moov Gabon integration with automatic routing
- 👥 **Contact Management** - Advanced contact organization with custom fields and groups
- 🚀 **Campaign Management** - Recurring campaigns, A/B testing, and scheduling
- 🔐 **Sub-Accounts** - Role-based access control for team collaboration
- 📝 **Template Library** - Reusable message templates with dynamic variables
- 🔗 **Webhooks** - Event-driven integrations with 12 event types
- 🛡️ **Security** - Blacklist management and comprehensive audit logging
- 🌍 **i18n** - French/English localization with Gabon timezone
- 📊 **Analytics** - Message history, campaign tracking, and delivery reports

---

## Quick Start

### Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2.x
- Node.js 18+

### Installation

```bash
# Clone repository
git clone https://github.com/yourusername/sendwave-pro.git
cd sendwave-pro

# Install PHP dependencies
composer install

# Install Node dependencies
cd resources
npm install
npm run build
cd ..

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate

# Start development server
php artisan serve
```

Visit `http://localhost:8000`

---

## Documentation

- **[Implementation Summary](IMPLEMENTATION_SUMMARY.md)** - Complete feature documentation
- **[API Documentation](API_DOCUMENTATION.md)** - Full API reference (Markdown)
- **[Swagger/OpenAPI](http://localhost:8000/api/documentation)** - Interactive API documentation (when server is running)
- **[Deployment Guide](DEPLOYMENT_GUIDE.md)** - Hostinger deployment instructions
- **[Roadmap](ROADMAP.md)** - Development roadmap and phases

### API Documentation

**Interactive Swagger UI**:
```bash
# Start development server
php artisan serve

# Access Swagger UI
http://localhost:8000/api/documentation

# Get OpenAPI JSON
http://localhost:8000/docs
```

The Swagger documentation provides:
- Interactive API testing
- Request/Response examples
- Authentication testing with Bearer tokens
- Complete endpoint reference
- Model schemas

---

## Platform Statistics

| Metric | Count |
|--------|-------|
| API Routes | 89+ |
| Database Tables | 19 |
| Models | 17 |
| Controllers | 13 |
| Services | 4 |
| Vue Components | 17 |
| Migrations | 27 |

---

## Tech Stack

**Backend**:
- Laravel 12
- MySQL
- Laravel Sanctum (Authentication)
- Laravel Queue (Job Processing)

**Frontend**:
- Vue 3 (Composition API)
- TypeScript
- Vite
- Tailwind CSS
- Heroicons

**SMS Providers**:
- Airtel Gabon API
- Moov Gabon API

**Integrations**:
- Webhooks (HMAC-SHA256 signatures)
- RESTful API

---

## Core Features

### 1. Contact Management
- Import contacts from CSV
- Custom fields (JSON-based)
- Contact groups with many-to-many relationships
- Bulk operations
- Status tracking

### 2. Campaign Management
- One-time and recurring campaigns
- A/B testing with up to 5 variants
- Dynamic message variables: `{nom}`, `{email}`, `{custom.field}`
- Automatic operator routing
- Campaign scheduling

### 3. Sub-Accounts
- 4 role types: admin, manager, sender, viewer
- Granular permissions
- Credit limits per sub-account
- Suspend/activate functionality
- Last connection tracking

### 4. Webhooks
12 event types:
- `message.sent`, `message.delivered`, `message.failed`
- `campaign.started`, `campaign.completed`, `campaign.failed`
- `contact.created`, `contact.updated`, `contact.deleted`
- `sub_account.created`, `sub_account.suspended`
- `blacklist.added`

Features:
- HMAC-SHA256 signatures
- Retry logic with exponential backoff
- Delivery logs and statistics
- Test endpoint

### 5. Security
- Blacklist management
- Comprehensive audit logs
- IP and user agent tracking
- Sanctum API authentication
- CSRF protection
- Rate limiting

---

## API Quick Reference

### Authentication
```bash
# Register
POST /api/auth/register
Content-Type: application/json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}

# Login
POST /api/auth/login
Content-Type: application/json
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Send SMS
```bash
POST /api/messages/send
Authorization: Bearer {token}
Content-Type: application/json
{
  "recipients": ["+24162000001", "+24177000002"],
  "message": "Hello {nom}, your code is 123456"
}
```

### Create Webhook
```bash
POST /api/webhooks
Authorization: Bearer {token}
Content-Type: application/json
{
  "name": "My Integration",
  "url": "https://myapp.com/webhooks/sendwave",
  "events": ["message.sent", "campaign.completed"]
}
```

See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete reference.

---

## Environment Configuration

```env
# Application
APP_NAME="SendWave Pro"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Africa/Libreville
APP_LOCALE=fr

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=sendwave_pro
DB_USERNAME=your_user
DB_PASSWORD=your_password

# SMS Providers
AIRTEL_CLIENT_ID=your_airtel_client_id
AIRTEL_CLIENT_SECRET=your_airtel_client_secret
AIRTEL_SENDER_ID=SENDWAVE

MOOV_API_KEY=your_moov_api_key
MOOV_SENDER_ID=SENDWAVE
```

---

## Development

### Run Migrations
```bash
php artisan migrate
```

### Seed Database (if seeders exist)
```bash
php artisan db:seed
```

### Build Frontend
```bash
cd resources
npm run dev      # Development with HMR
npm run build    # Production build
```

### Run Tests
```bash
php artisan test
```

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Production Deployment

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for detailed instructions.

Quick checklist:
- ✅ Set `APP_DEBUG=false`
- ✅ Run `composer install --optimize-autoloader --no-dev`
- ✅ Run `npm run build`
- ✅ Set up cron job for scheduler
- ✅ Configure SSL certificate
- ✅ Set correct file permissions
- ✅ Run migrations
- ✅ Cache config/routes/views

---

## Cron Configuration

Add to crontab for recurring campaigns:
```bash
* * * * * cd /path/to/sendwave-pro && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security

### Best Practices Implemented
- ✅ CSRF protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS protection (auto-escaping)
- ✅ Rate limiting on sensitive endpoints
- ✅ Secure password hashing (bcrypt)
- ✅ API token authentication (Sanctum)
- ✅ Audit logging for all actions
- ✅ HTTPS enforcement in production

### Webhook Security
All webhooks include HMAC-SHA256 signature in `X-Webhook-Signature` header.

Verify signatures:
```php
$signature = hash_hmac('sha256', $payload, $secret);
if (hash_equals($signature, $receivedSignature)) {
    // Valid
}
```

---

## Support & Contribution

### Reporting Issues
Please use the GitHub issue tracker for bug reports and feature requests.

### Contributing
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

---

## License

Proprietary - All rights reserved

---

## Credits

**Developed**: November 2025
**Framework**: Laravel 12 + Vue 3
**Target Market**: Gabon (Airtel/Moov)

Built with [Claude Code](https://claude.com/claude-code)

---

## Changelog

### v2.1 (2025-11-07)
- ✨ Added Webhooks system (12 event types)
- ✨ HMAC-SHA256 webhook signatures
- ✨ Retry logic with exponential backoff
- ✨ Webhook delivery logs and statistics
- 📚 Added API documentation
- 📚 Added deployment guide
- ⚡ Optimized .htaccess for production
- 🔒 Enhanced security headers

### v2.0 (2025-11-07)
- ✨ Sub-accounts with role-based access
- ✨ Contact groups and custom fields
- ✨ Recurring campaigns
- ✨ A/B testing
- ✨ Dynamic message variables
- ✨ Template library
- ✨ Blacklist management
- ✨ Audit logging
- 🌍 French/English localization
- 📊 Message history and analytics

---

**For detailed documentation, see the `/docs` directory or individual markdown files in the project root.**
