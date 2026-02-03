<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SendWave Pro API",
 *     version="2.1",
 *     description="Enterprise SMS Campaign Management Platform - API Documentation for Gabon Market (Airtel/Moov)",
 *     @OA\Contact(
 *         email="support@sendwave-pro.com",
 *         name="SendWave Pro Support"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://sendwave-pro.com/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\Server(
 *     url="https://yourdomain.com",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Contacts",
 *     description="Contact management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Contact Groups",
 *     description="Contact group management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Campaigns",
 *     description="SMS campaign management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Messages",
 *     description="SMS sending and message history endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Templates",
 *     description="Message template management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Sub-Accounts",
 *     description="Sub-account management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Webhook management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Blacklist",
 *     description="Phone number blacklist endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="Activity audit log endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Analytics",
 *     description="Dashboard analytics, charts and report endpoints"
 * )
 *
 * @OA\Tag(
 *     name="SMS Analytics",
 *     description="SMS accounting, period closures and detailed analytics"
 * )
 *
 * @OA\Tag(
 *     name="Budget",
 *     description="Budget management and monthly limits endpoints"
 * )
 *
 * @OA\Tag(
 *     name="API Keys",
 *     description="API key management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="SMS Config",
 *     description="SMS operator configuration endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Phone",
 *     description="Phone number normalization and validation"
 * )
 *
 * @OA\Tag(
 *     name="Accounts",
 *     description="Account management endpoints (SuperAdmin)"
 * )
 *
 * @OA\Tag(
 *     name="Users",
 *     description="User management within an account"
 * )
 *
 * @OA\Tag(
 *     name="Custom Roles",
 *     description="Custom role management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="SMS Providers",
 *     description="SMS provider definition endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Incoming SMS",
 *     description="Incoming SMS webhook endpoints"
 * )
 */
abstract class Controller
{
    //
}
