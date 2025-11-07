# SendWave Pro - API Documentation

**Version**: 2.1
**Base URL**: `https://yourdomain.com/api`
**Authentication**: Laravel Sanctum (Bearer Token)

---

## Table of Contents

1. [Authentication](#authentication)
2. [Contacts](#contacts)
3. [Contact Groups](#contact-groups)
4. [Campaigns](#campaigns)
5. [Messages](#messages)
6. [Templates](#templates)
7. [Sub-Accounts](#sub-accounts)
8. [Webhooks](#webhooks)
9. [Blacklist](#blacklist)
10. [Audit Logs](#audit-logs)
11. [Error Codes](#error-codes)

---

## Authentication

### Register
```http
POST /auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response 201**:
```json
{
  "message": "Inscription réussie",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123..."
  }
}
```

### Login
```http
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response 200**:
```json
{
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|xyz789..."
  }
}
```

### Get Current User
```http
GET /auth/me
Authorization: Bearer {token}
```

### Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

---

## Contacts

### List All Contacts
```http
GET /contacts
Authorization: Bearer {token}
```

**Response 200**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Alice Smith",
      "email": "alice@example.com",
      "phone": "+24162000000",
      "group": "Clients",
      "status": "active",
      "custom_fields": {
        "company": "ABC Corp",
        "position": "Manager"
      },
      "created_at": "2025-11-07T10:00:00Z"
    }
  ]
}
```

### Create Contact
```http
POST /contacts
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Alice Smith",
  "email": "alice@example.com",
  "phone": "+24162000000",
  "group": "Clients",
  "status": "active",
  "custom_fields": {
    "company": "ABC Corp"
  }
}
```

**Triggers Webhook**: `contact.created`

### Update Contact
```http
PUT /contacts/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Alice Johnson",
  "status": "inactive"
}
```

**Triggers Webhook**: `contact.updated`

### Delete Contact
```http
DELETE /contacts/{id}
Authorization: Bearer {token}
```

**Triggers Webhook**: `contact.deleted`

### Import Contacts (CSV)
```http
POST /contacts/import
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: contacts.csv
```

**CSV Format**:
```csv
name,email,phone,group,status
John Doe,john@example.com,+24162000001,Clients,active
Jane Smith,jane@example.com,+24162000002,Partners,active
```

---

## Contact Groups

### List All Groups
```http
GET /contact-groups
Authorization: Bearer {token}
```

### Create Group
```http
POST /contact-groups
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "VIP Clients",
  "description": "High-value customers"
}
```

### Add Contacts to Group
```http
POST /contact-groups/{id}/contacts/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "contact_ids": [1, 2, 3, 4]
}
```

### Remove Contacts from Group
```http
POST /contact-groups/{id}/contacts/remove
Authorization: Bearer {token}
Content-Type: application/json

{
  "contact_ids": [2, 3]
}
```

### Get Group Contacts
```http
GET /contact-groups/{id}/contacts
Authorization: Bearer {token}
```

---

## Campaigns

### Create Campaign
```http
POST /campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Black Friday Sale",
  "message_content": "🎉 50% OFF everything! Visit our store today.",
  "scheduled_at": "2025-11-29T09:00:00Z"
}
```

### Send Campaign
```http
POST /campaigns/{id}/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "recipients": ["+24162000001", "+24177000002"],
  "message": "Hello {nom}, check out our new products!"
}
```

**Triggers Webhooks**: `campaign.started`, `campaign.completed`, or `campaign.failed`

### Schedule Recurring Campaign
```http
POST /campaigns/{id}/schedule
Authorization: Bearer {token}
Content-Type: application/json

{
  "frequency": "weekly",
  "day_of_week": 1,
  "time": "09:00",
  "start_date": "2025-11-10",
  "end_date": "2025-12-31",
  "is_active": true
}
```

**Frequencies**: `once`, `daily`, `weekly`, `monthly`

### Create A/B Testing Variants
```http
POST /campaigns/{id}/variants
Authorization: Bearer {token}
Content-Type: application/json

{
  "variants": [
    {
      "variant_name": "Version A",
      "message": "Get 20% OFF now!",
      "percentage": 50
    },
    {
      "variant_name": "Version B",
      "message": "Limited time: 20% discount!",
      "percentage": 50
    }
  ]
}
```

**Note**: Percentages must sum to 100%

---

## Messages

### Send Single/Bulk SMS
```http
POST /messages/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "recipients": ["+24162000001", "+24177000002"],
  "message": "Your verification code is: 123456"
}
```

**Rate Limited**: 60 requests per minute

**Triggers Webhook**: `message.sent` or `message.failed`

**Response 200**:
```json
{
  "message": "Envoi terminé",
  "data": {
    "total": 2,
    "sent": 2,
    "failed": 0,
    "sms_count": 1,
    "total_cost": 40,
    "by_operator": {
      "airtel": 1,
      "moov": 1,
      "unknown": 0
    },
    "message_ids": [1, 2]
  }
}
```

### Analyze Phone Numbers
```http
POST /messages/analyze
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_numbers": ["+24162000001", "+24177000002", "0777000003"]
}
```

**Response 200**:
```json
{
  "message": "Analyse effectuée",
  "data": {
    "total": 3,
    "airtel_count": 1,
    "moov_count": 2,
    "unknown_count": 0,
    "details": [
      {
        "phone": "+24162000001",
        "operator": "airtel",
        "is_valid": true
      }
    ]
  }
}
```

### Get Message History
```http
GET /messages/history?status=sent&start_date=2025-11-01&end_date=2025-11-07
Authorization: Bearer {token}
```

### Export Message History
```http
GET /messages/export?format=csv&start_date=2025-11-01
Authorization: Bearer {token}
```

---

## Templates

### List Templates
```http
GET /templates?category=marketing&is_public=true
Authorization: Bearer {token}
```

### Create Template
```http
POST /templates
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Welcome Message",
  "content": "Bonjour {nom}, bienvenue chez nous! Votre email: {email}",
  "category": "notifications",
  "is_public": false
}
```

**Categories**: `marketing`, `notifications`, `alerts`, `reminders`, `confirmations`, `promotions`, `other`

### Get Template Categories
```http
GET /templates/categories
Authorization: Bearer {token}
```

### Preview Template
```http
POST /templates/{id}/preview
Authorization: Bearer {token}
Content-Type: application/json

{
  "sample_data": {
    "{nom}": "Jean Dupont",
    "{email}": "jean@example.com"
  }
}
```

### Use Template (Increment Usage Count)
```http
POST /templates/{id}/use
Authorization: Bearer {token}
```

---

## Sub-Accounts

### List Sub-Accounts
```http
GET /sub-accounts
Authorization: Bearer {token}
```

### Create Sub-Account
```http
POST /sub-accounts
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Manager User",
  "email": "manager@example.com",
  "password": "secure123",
  "role": "manager",
  "sms_credit_limit": 1000
}
```

**Roles**: `admin`, `manager`, `sender`, `viewer`

**Triggers Webhook**: `sub_account.created`

### Update Permissions
```http
POST /sub-accounts/{id}/permissions
Authorization: Bearer {token}
Content-Type: application/json

{
  "permissions": [
    "send_sms",
    "view_history",
    "manage_contacts"
  ]
}
```

**Available Permissions**:
- `send_sms`
- `view_history`
- `manage_contacts`
- `manage_groups`
- `create_campaigns`
- `view_analytics`
- `manage_templates`
- `export_data`

### Suspend Sub-Account
```http
POST /sub-accounts/{id}/suspend
Authorization: Bearer {token}
```

**Triggers Webhook**: `sub_account.suspended`

### Activate Sub-Account
```http
POST /sub-accounts/{id}/activate
Authorization: Bearer {token}
```

### Add Credits
```http
POST /sub-accounts/{id}/credits
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 500
}
```

---

## Webhooks

### List All Webhooks
```http
GET /webhooks
Authorization: Bearer {token}
```

### Get Available Events
```http
GET /webhooks/events
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "message.sent": "Message envoyé",
    "message.delivered": "Message délivré",
    "message.failed": "Message échoué",
    "campaign.started": "Campagne démarrée",
    "campaign.completed": "Campagne terminée",
    "campaign.failed": "Campagne échouée",
    "contact.created": "Contact créé",
    "contact.updated": "Contact mis à jour",
    "contact.deleted": "Contact supprimé",
    "sub_account.created": "Sous-compte créé",
    "sub_account.suspended": "Sous-compte suspendu",
    "blacklist.added": "Numéro ajouté à la liste noire"
  }
}
```

### Create Webhook
```http
POST /webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "My Integration",
  "url": "https://myapp.com/webhooks/sendwave",
  "events": ["message.sent", "campaign.completed"],
  "secret": "optional_custom_secret",
  "retry_limit": 3,
  "timeout": 30,
  "is_active": true
}
```

**Auto-generated**: If `secret` is not provided, a 32-character secret is generated

### Get Webhook Details
```http
GET /webhooks/{id}
Authorization: Bearer {token}
```

**Includes**: Last 10 delivery logs

### Update Webhook
```http
PUT /webhooks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "events": ["message.sent", "message.failed", "contact.created"],
  "is_active": false
}
```

### Test Webhook
```http
POST /webhooks/{id}/test
Authorization: Bearer {token}
```

**Sends Test Payload**:
```json
{
  "event": "webhook.test",
  "timestamp": "2025-11-07T12:00:00+00:00",
  "data": {
    "message": "This is a test webhook delivery",
    "webhook_id": 1,
    "webhook_name": "My Integration"
  }
}
```

**Headers Sent**:
- `X-Webhook-Signature`: HMAC-SHA256 signature
- `X-Webhook-Event`: webhook.test
- `Content-Type`: application/json

### Toggle Webhook Status
```http
POST /webhooks/{id}/toggle
Authorization: Bearer {token}
```

### Get Webhook Logs
```http
GET /webhooks/{id}/logs?event=message.sent&success=true
Authorization: Bearer {token}
```

### Get Webhook Statistics
```http
GET /webhooks/{id}/stats
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "total_triggers": 150,
    "successful": 145,
    "failed": 5,
    "success_rate": 96.67,
    "last_triggered_at": "2025-11-07T12:00:00Z",
    "events_by_type": [
      {
        "event": "message.sent",
        "count": 100
      },
      {
        "event": "campaign.completed",
        "count": 50
      }
    ]
  }
}
```

### Verify Webhook Signature (Your Server)

When receiving webhooks, verify the signature:

```php
<?php
// PHP Example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (hash_equals($expectedSignature, $signature)) {
    // Signature is valid
    $data = json_decode($payload, true);
    // Process the webhook
} else {
    // Invalid signature
    http_response_code(401);
}
```

```javascript
// Node.js Example
const crypto = require('crypto');

function verifyWebhook(payload, signature, secret) {
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');

  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}
```

---

## Blacklist

### List Blacklisted Numbers
```http
GET /blacklist
Authorization: Bearer {token}
```

### Add to Blacklist
```http
POST /blacklist
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+24162000000",
  "reason": "Requested opt-out"
}
```

**Triggers Webhook**: `blacklist.added`

### Remove from Blacklist
```http
DELETE /blacklist/{id}
Authorization: Bearer {token}
```

### Check if Number is Blacklisted
```http
POST /blacklist/check
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+24162000000"
}
```

**Response**:
```json
{
  "phone_number": "+24162000000",
  "is_blacklisted": true
}
```

---

## Audit Logs

### List Audit Logs
```http
GET /audit-logs?action=contact.created&start_date=2025-11-01
Authorization: Bearer {token}
```

**Query Parameters**:
- `action`: Filter by action type
- `start_date`: Filter from date
- `end_date`: Filter to date
- `model_type`: Filter by model (e.g., "App\\Models\\Contact")
- `model_id`: Filter by specific record ID

### Get Audit Log Details
```http
GET /audit-logs/{id}
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "sub_account_id": null,
    "action": "contact.created",
    "model_type": "App\\Models\\Contact",
    "model_id": 5,
    "old_values": null,
    "new_values": {
      "name": "John Doe",
      "email": "john@example.com"
    },
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "created_at": "2025-11-07T12:00:00Z"
  }
}
```

### Get Available Actions
```http
GET /audit-logs/actions
Authorization: Bearer {token}
```

---

## Error Codes

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (Rate Limited) |
| 500 | Internal Server Error |

### Error Response Format

```json
{
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

### Common Validation Errors

**422 Unprocessable Entity**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "phone": ["The phone format is invalid."]
  }
}
```

**401 Unauthorized**:
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**:
```json
{
  "message": "Cette action n'est pas autorisée."
}
```

**429 Too Many Requests**:
```json
{
  "message": "Too Many Attempts."
}
```

---

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| `/messages/send` | 60 requests/minute |
| Other endpoints | 120 requests/minute |

**Headers Included**:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `Retry-After`: Seconds to wait (on 429 error)

---

## Dynamic Variables in Messages

Use these variables in message content for personalization:

| Variable | Description | Example |
|----------|-------------|---------|
| `{nom}` | Contact's full name | Jean Dupont |
| `{prenom}` | Contact's first name | Jean |
| `{email}` | Contact's email | jean@example.com |
| `{telephone}` | Contact's phone | +24162000000 |
| `{custom.field_name}` | Custom field value | {custom.company} |

**Example**:
```
Message: "Bonjour {nom}, votre email {email} a été confirmé!"
Result: "Bonjour Jean Dupont, votre email jean@example.com a été confirmé!"
```

---

## Best Practices

### Authentication
- Store tokens securely (never in localStorage for web apps)
- Implement token refresh mechanism
- Use HTTPS only in production

### Webhooks
- Always verify webhook signatures
- Respond with 200 status quickly (< 5 seconds)
- Process webhook data asynchronously
- Implement idempotency (webhooks may retry)

### Rate Limiting
- Implement exponential backoff on rate limit errors
- Cache responses when possible
- Batch operations where available

### Error Handling
- Log all API errors for debugging
- Implement retry logic for network failures
- Handle validation errors gracefully

---

**API Version**: 2.1
**Last Updated**: November 7, 2025
**Support**: For API support, contact your system administrator
