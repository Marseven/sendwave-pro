# SendWave Pro (JOBS SMS)

**Enterprise SMS Campaign Management Platform**

Version 3.1 | Production Ready | Built with Laravel 11 + Vue 3 + TypeScript

---

## Overview

SendWave Pro (JOBS SMS) is a comprehensive SMS campaign management platform designed for the African market, with primary focus on Gabon. It supports multiple operators (Airtel, Moov) with automatic routing, intelligent fallback mechanism, and operator detection.

### Key Features

- **Multi-Provider SMS** - Airtel & Moov integration with automatic routing and fallback
- **Contact Management** - Advanced contact organization with custom fields, groups, and databases
- **Campaign Management** - Recurring campaigns, A/B testing, scheduling, and calendar view
- **Sub-Accounts** - Role-based access control with budget management for team collaboration
- **Template Library** - Reusable message templates with dynamic variables and categories
- **Webhooks** - Event-driven integrations with 14 event types and HMAC signatures
- **Transactional SMS** - Dedicated interface for Sender IDs, templates, drafts, and routes
- **STOP Management** - Automatic blacklisting via STOP keywords (FR/EN support)
- **E.164 Normalization** - International phone format support for 5 African countries
- **Security** - Blacklist management, audit logging, and API key management
- **i18n** - French/English localization with Gabon timezone (Africa/Libreville)
- **Analytics** - Message history, campaign tracking, budget monitoring, and delivery reports

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
- **[Swagger/OpenAPI](http://localhost:8000/api/documentation)** - Interactive API documentation
- **[Deployment Guide](DEPLOYMENT_GUIDE.md)** - Hostinger deployment instructions
- **[Action Plan V2](PLAN_ACTION_V2.md)** - Development roadmap and phases
- **[Claude.md](CLAUDE.md)** - Developer onboarding documentation

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

---

## Platform Statistics

| Metric | Count |
|--------|-------|
| API Routes | 102+ |
| Database Tables | 23 |
| Models | 19 |
| Controllers | 15 |
| Services | 16 |
| Vue Views | 24 |
| Vue Components | 8 |
| Migrations | 31 |
| Unit Tests | 46 |

---

## Tech Stack

**Backend**:
- Laravel 11 (PHP 8.2+)
- MySQL 5.7+
- Laravel Sanctum (Authentication)
- Laravel Queue (Job Processing)
- Laravel Scheduler (Recurring campaigns)

**Frontend**:
- Vue 3 (Composition API)
- TypeScript
- Vite
- Tailwind CSS
- Heroicons
- Pinia (State Management)

**SMS Providers**:
- Airtel Gabon API
- Moov Gabon API
- Automatic Fallback Mechanism

**Integrations**:
- Webhooks (HMAC-SHA256 signatures)
- RESTful API
- Incoming SMS Webhooks

---

## Core Features

### 1. Contact Management
- Import contacts from CSV/Excel
- Custom fields (JSON-based)
- Contact groups with many-to-many relationships
- Contact databases for organization
- Bulk operations (delete, export)
- Status tracking and operator detection

### 2. Campaign Management
- One-time and recurring campaigns
- A/B testing with up to 5 variants
- Dynamic message variables: `{nom}`, `{email}`, `{custom.field}`
- Automatic operator routing with fallback
- Campaign scheduling with calendar view
- Campaign history and statistics

### 3. Sub-Accounts
- 4 role types: admin, manager, sender, viewer
- Granular permissions
- Monthly budget limits with alerts
- Block on budget exceeded option
- Credit tracking per sub-account
- Suspend/activate functionality

### 4. Transactional SMS
- Sender ID management with approval status
- Template management with categories
- Draft messages for quick access
- Route configuration with fallback toggle
- Dedicated interface for API users

### 5. Webhooks
14 event types:
- `message.sent`, `message.delivered`, `message.failed`
- `message.received` (incoming SMS)
- `campaign.started`, `campaign.completed`, `campaign.failed`
- `contact.created`, `contact.updated`, `contact.deleted`
- `contact.unsubscribed` (STOP keyword)
- `sub_account.created`, `sub_account.suspended`
- `blacklist.added`

Features:
- HMAC-SHA256 signatures
- Retry logic with exponential backoff
- Delivery logs and statistics
- Test endpoint

### 6. STOP Word Management
- Automatic STOP keyword detection
- French keywords: STOP, ARRET, ARRÊT, DESABONNER, DESINSCRIPTION
- English keywords: UNSUBSCRIBE, UNSUB, REMOVE, QUIT, END, CANCEL, OPTOUT
- Accent normalization (é → e, ê → e)
- Automatic blacklist insertion
- Webhook notification on unsubscribe

### 7. Phone Normalization
- E.164 international format support
- 5 African countries: Gabon (GA), Cameroon (CM), Congo (CG), Côte d'Ivoire (CI), Senegal (SN)
- Operator detection for each country
- Local to international conversion
- Batch normalization support

### 8. Security
- Blacklist management with source tracking
- Comprehensive audit logs
- IP and user agent tracking
- Sanctum API authentication
- API key management
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

### Normalize Phone Numbers
```bash
POST /api/phone/normalize
Authorization: Bearer {token}
Content-Type: application/json
{
  "phones": ["77123456", "+241 62 00 00 01"],
  "country": "GA"
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
  "events": ["message.sent", "message.received", "contact.unsubscribed"]
}
```

See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete reference.

---

## Environment Configuration

```env
# Application
APP_NAME="JOBS SMS"
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
AIRTEL_SENDER_ID=JOBSSMS

MOOV_API_KEY=your_moov_api_key
MOOV_SENDER_ID=JOBSSMS

# Fallback Configuration
SMS_FALLBACK_ENABLED=true
SMS_PRIMARY_PROVIDER=airtel
SMS_FALLBACK_PROVIDER=moov
```

---

## Development

### Run Migrations
```bash
php artisan migrate
```

### Seed Database
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
# All tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# Integration tests (requires MySQL)
php artisan test --group=integration
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
- Set `APP_DEBUG=false`
- Run `composer install --optimize-autoloader --no-dev`
- Run `npm run build`
- Set up cron job for scheduler
- Configure SSL certificate
- Set correct file permissions
- Run migrations
- Cache config/routes/views

---

## Cron Configuration

Add to crontab for recurring campaigns and scheduled tasks:
```bash
* * * * * cd /path/to/sendwave-pro && php artisan schedule:run >> /dev/null 2>&1
```

---

## Security

### Best Practices Implemented
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection (auto-escaping)
- Rate limiting on sensitive endpoints
- Secure password hashing (bcrypt)
- API token authentication (Sanctum)
- API key management with hashed secrets
- Audit logging for all actions
- HTTPS enforcement in production

### Webhook Security
All webhooks include HMAC-SHA256 signature in `X-Webhook-Signature` header.

Verify signatures:
```php
$signature = hash_hmac('sha256', $payload, $secret);
if (hash_equals($signature, $receivedSignature)) {
    // Valid webhook
}
```

### Incoming SMS Webhooks
Receive SMS from providers at:
- `POST /api/webhooks/incoming/sms` - Generic endpoint
- `POST /api/webhooks/incoming/airtel` - Airtel-specific
- `POST /api/webhooks/incoming/moov` - Moov-specific

---

## Project Structure

```
sendwave-pro/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Enums/               # WebhookEvent enum
│   ├── Events/              # Budget events
│   ├── Http/Controllers/    # API & Web controllers
│   ├── Jobs/                # Queue jobs
│   ├── Listeners/           # Event listeners
│   ├── Models/              # Eloquent models (19)
│   └── Services/            # Business logic (16)
├── resources/
│   └── src/
│       ├── components/      # Vue components
│       ├── stores/          # Pinia stores
│       ├── types/           # TypeScript definitions
│       └── views/           # Vue views (24)
├── routes/
│   ├── api.php             # API routes
│   └── web.php             # Web routes
└── tests/
    ├── Feature/            # Integration tests
    └── Unit/               # Unit tests
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

**Developed**: November 2025 - January 2026
**Framework**: Laravel 11 + Vue 3
**Target Market**: Gabon (Airtel/Moov), Cameroon, Congo, Côte d'Ivoire, Senegal

Built with [Claude Code](https://claude.com/claude-code)

---

## Changelog

### v3.1 (2026-01-25)
- Transactional SMS interface with 4 tabs (Sender ID, Templates, Drafts, Routes)
- STOP word management with French/English keywords
- E.164 phone normalization for 5 African countries
- Incoming SMS webhook endpoints
- Fallback mechanism (Airtel ↔ Moov)
- Budget management with alerts and blocking
- Comprehensive unit tests (46 tests)
- Full codebase audit and documentation update

### v3.0 (2026-01-20)
- Analytical accounting (SmsAnalytics)
- Vue.js interface refactoring
- Calendar view for scheduled campaigns
- Campaign and message history views
- SMS operator configuration
- Audit logs interface

### v2.1 (2025-11-07)
- Webhooks system (12 event types)
- HMAC-SHA256 webhook signatures
- Retry logic with exponential backoff
- Webhook delivery logs and statistics
- API documentation
- Deployment guide

### v2.0 (2025-11-07)
- Sub-accounts with role-based access
- Contact groups and custom fields
- Recurring campaigns
- A/B testing
- Dynamic message variables
- Template library
- Blacklist management
- Audit logging
- French/English localization

---

**For detailed documentation, see the `/docs` directory or individual markdown files in the project root.**
