<?php

namespace App\Http;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Meem Market API",
    description: "API documentation for Meem Market.\n\nSwagger Testing Tips:\n1) Open docs from http://127.0.0.1:8000/api/documentation or http://localhost:8000/api/documentation (not a file:// URL).\n2) If you see \"Failed to fetch\", verify APP_URL, server URL selection in Swagger, and CORS allowed origins.\n3) Authenticate using /api/v1/admin/auth/login, then click Authorize with: Bearer <token>.",
    contact: new OA\Contact(email: "admin@meem-market.com"),
    license: new OA\License(name: "Apache 2.0", url: "http://www.apache.org/licenses/LICENSE-2.0.html")
)]
#[OA\Server(
    url: "http://127.0.0.1:8000",
    description: "Local API Server (127.0.0.1)"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local API Server (localhost)"
)]
#[OA\Server(
    url: "http://api.meem-market.com/",
    description: "Production Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum",
    description: "Enter the token returned from /api/v1/admin/auth/login"
)]
#[OA\Schema(
    schema: "AdminErrorResponse",
    type: "object",
    required: ["success", "message", "code"],
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "You do not have permission to perform this action."),
        new OA\Property(property: "code", type: "string", example: "FORBIDDEN"),
    ]
)]
#[OA\Schema(
    schema: "AdminValidationErrorResponse",
    type: "object",
    required: ["success", "message", "code", "errors"],
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "Validation failed."),
        new OA\Property(property: "code", type: "string", example: "VALIDATION_ERROR"),
        new OA\Property(
            property: "errors",
            type: "object",
            additionalProperties: new OA\AdditionalProperties(
                type: "array",
                items: new OA\Items(type: "string")
            )
        ),
    ]
)]
class Swagger {}
