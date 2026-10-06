<?php

declare(strict_types=1);

namespace App\Health;

use App\Erp\ErpClientInterface;

class ErpHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
    ) {
    }

    public function name(): string
    {
        return 'erp';
    }

    public function isHealthy(): bool
    {
        return $this->erpClient->ping()['status'] === 'ok';
    }
}
