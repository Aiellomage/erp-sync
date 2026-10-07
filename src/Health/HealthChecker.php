<?php

declare(strict_types=1);

namespace App\Health;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class HealthChecker
{
    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        #[AutowireIterator(HealthCheckInterface::TAG)]
        private readonly iterable $checks,
    ) {
    }

    /**
     * @return array<string, bool>
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[$check->name()] = $check->isHealthy();
        }

        return $results;
    }
}
