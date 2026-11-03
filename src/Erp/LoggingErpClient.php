<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

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
        try {
            $result = $this->inner->ping();
        } catch (\Throwable $e) {
            $this->logFailure('ping', $start, $e);
            throw $e;
        }

        $this->erpLogger->info('ERP ping in {duration_ms} ms', ['duration_ms' => $this->elapsedMs($start)]);

        return $result;
    }

    /** @return list<ErpProduct> */
    public function fetchProducts(?DateTimeImmutable $since = null): array
    {
        $start = hrtime(true);
        try {
            $result = $this->inner->fetchProducts($since);
        } catch (\Throwable $e) {
            // Log and rethrow: the decorator records the failure, the caller decides what to do
            $this->logFailure('fetchProducts', $start, $e);
            throw $e;
        }

        $this->erpLogger->info('ERP fetchProducts since {since}: {count} products in {duration_ms} ms', [
            'since' => $since?->format(DATE_ATOM) ?? 'always',
            'count' => count($result),
            'duration_ms' => $this->elapsedMs($start),
        ]);

        return $result;
    }

    private function logFailure(string $operation, int $start, \Throwable $e): void
    {
        $this->erpLogger->error('ERP {operation} failed after {duration_ms} ms: {error} ({type})', [
            'operation' => $operation,
            'duration_ms' => $this->elapsedMs($start),
            'error' => $e->getMessage(),
            'type' => (new \ReflectionClass($e))->getShortName(),
            'exception' => $e,
        ]);
    }

    private function elapsedMs(int $start): float
    {
        return round((hrtime(true) - $start) / 1_000_000, 2);
    }
}
