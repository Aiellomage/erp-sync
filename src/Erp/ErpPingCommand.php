<?php

declare(strict_types=1);

namespace App\Erp;

use App\Service\ErpPinger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:erp:ping', description: 'Checks that the ERP responds')]
class ErpPingCommand
{
    public function __construct(
        private readonly ErpPinger $erpPinger,
        private readonly ErpClientInterface $catalogClient,
        private readonly ErpClientInterface $stockClient,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'ERP endpoint to ping: default, catalog or stock')]
        string $endpoint = 'default',
    ): int {
        $result = match ($endpoint) {
            'default' => $this->erpPinger->ping(),
            'catalog' => $this->catalogClient->ping(),
            'stock' => $this->stockClient->ping(),
            default => null,
        };

        if ($result === null) {
            $io->error(sprintf('Unknown endpoint "%s": use default, catalog or stock', $endpoint));

            return Command::INVALID;
        }

        if ($result['status'] === 'ok') {
            $io->success(sprintf('ERP "%s" responds (%s)', $result['erp'], $result['base_url'] ?? 'n/a'));

            return Command::SUCCESS;
        }

        $io->error(sprintf('ERP "%s" returned status "%s"', $result['erp'], $result['status']));

        return Command::FAILURE;
    }
}
