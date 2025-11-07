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
 */
abstract class Controller
{
    //
}
