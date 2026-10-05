<?php
declare(strict_types=1);

namespace App\Erp;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HttpErpClient implements ErpClientInterface
{
    public function __construct(
        #[Autowire(env: 'ERP_BASE_URL')]
        private readonly string $baseUrl,
    ) {
    }

    public function ping(): array
    {
        return ['erp' => 'http-erp', 'status' => 'ok', 'base_url' => $this->baseUrl];
    }
}
