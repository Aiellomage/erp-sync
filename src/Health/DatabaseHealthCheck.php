<?php

declare(strict_types=1);

namespace App\Health;

use Doctrine\DBAL\Connection;

class DatabaseHealthCheck implements HealthCheckInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function name(): string
    {
        return 'database';
    }

    public function isHealthy(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
