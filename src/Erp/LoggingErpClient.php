<?php
declare(strict_types=1);

namespace App\Erp;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Psr\Log\LoggerInterface;

#[AsDecorator(decorates: ErpClientInterface::class)]
class LoggingErpClient implements ErpClientInterface
{

    public function __construct(
        #[AutowireDecorated]
        private readonly ErpClientInterface $inner,
        private readonly LoggerInterface    $logger
    )
    {
    }

    public function ping(): array
    {
        $start = hrtime(true);
        $result = $this->inner->ping();
        $durationMs = round((hrtime(true) - $start) / 1000000, 2);
        $this->logger->info('ERP ping in {duration_ms} ms', ['duration_ms' => $durationMs]);
        return $result;
    }
}
