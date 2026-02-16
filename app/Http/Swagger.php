<?php

namespace App\Http;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Meem Market API",
    description: "API documentation for Meem Market",
    contact: new OA\Contact(email: "admin@meem-market.com"),
    license: new OA\License(name: "Apache 2.0", url: "http://www.apache.org/licenses/LICENSE-2.0.html")
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Demo API Server"
)]
class Swagger
{
}
