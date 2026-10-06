<?php

declare(strict_types=1);

namespace App\Health;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::TAG)]
interface HealthCheckInterface
{
    public const TAG = 'app.health_check';

    public function name(): string;

    public function isHealthy(): bool;
}
