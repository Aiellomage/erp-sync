<?php
declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Psr\Log\LoggerInterface;

#[AsDecorator(decorates: ErpClientInterface::class)]
class LoggingErpClient implements ErpClientInterface
{

    public function __construct(
        #[AutowireDecorated]
        private readonly ErpClientInterface $inner,
        private readonly LoggerInterface $erpLogger,
    ) {
    }

    public function ping(): array
    {
        $start = hrtime(true);
        $result = $this->inner->ping();
        $durationMs = round((hrtime(true) - $start) / 1000000, 2);
        $this->erpLogger->info('ERP ping in {duration_ms} ms', ['duration_ms' => $durationMs]);
        return $result;
    }

    /** @return list<ErpProduct> */
    public function fetchProducts(?DateTimeImmutable $since = null): array
    {
        $start = hrtime(true);
        $result = $this->inner->fetchProducts($since);
        $durationMs = round((hrtime(true) - $start) / 1000000, 2);
        $this->erpLogger->info('ERP fetchProducts since {since}: {count} products in {duration_ms} ms', [
    'since' => $since?->format(DATE_ATOM) ?? 'always',
    'count' => count($result),
    'duration_ms' => $durationMs,
]);

        return $result;
    }
}
