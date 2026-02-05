<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SendWave Pro API",
 *     version="2.1",
 *     description="Plateforme d'envoi de SMS pour le marché gabonais (Airtel/Moov). Utilisez votre clé API (header X-API-Key) ou un Bearer token pour accéder aux endpoints.",
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
 *     url="http://161.35.159.160",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token Bearer obtenu via POST /api/auth/login"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key",
 *     description="Clé API générée depuis le tableau de bord SendWave Pro"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Inscription, connexion et gestion du profil"
 * )
 *
 * @OA\Tag(
 *     name="Messages",
 *     description="Envoi de SMS, OTP et analyse de numéros"
 * )
 *
 * @OA\Tag(
 *     name="Contacts",
 *     description="Gestion des contacts (CRUD)"
 * )
 */
abstract class Controller
{
    //
}
