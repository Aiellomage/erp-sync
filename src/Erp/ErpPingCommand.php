<?php

declare(strict_types=1);

namespace App\Erp;

use App\Service\ErpPinger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:erp:ping', description: 'Checks that the ERP responds')]
class ErpPingCommand
{
    public function __construct(
        private readonly ErpPinger $erpPinger,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $result = $this->erpPinger->ping();

        if ($result['status'] === 'ok') {
            $io->success(sprintf('ERP "%s" responds', $result['erp']));

            return Command::SUCCESS;
        }

        $io->error(sprintf('ERP "%s" returned status "%s"', $result['erp'], $result['status']));

        return Command::FAILURE;
    }
}
