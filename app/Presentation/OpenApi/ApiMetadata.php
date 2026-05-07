<?php

declare(strict_types=1);

namespace App\Presentation\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.3',
    info: new OA\Info(version: '1.0.0', title: 'CryptoSentinel'),
    servers: [
        new OA\Server(url: '/'),
    ]
)]
final class ApiMetadata
{
}
