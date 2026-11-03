<?php

declare(strict_types=1);

namespace App\FakeErp;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[AsCommand(name: 'app:fake-erp:fail', description: 'Makes the fake ERP fail on its next calls (dev only)')]
final readonly class FakeErpFailCommand
{
    public function __construct(
        private FakeErpFaults $faults,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: '500, 503, 429, 400, slow, badjson, missingfield, wrongkey, off or status')]
        string $mode = 'status',
        #[Option(description: 'How many calls fail before the ERP answers normally')]
        int $times = 1,
    ): int {
        if ($mode === 'status') {
            $fault = $this->faults->current();
            $io->writeln($fault === null
                ? 'Fake ERP: no fault, it answers normally'
                : sprintf('Fake ERP: next %d call(s) fail with "%s"', $fault['remaining'], $fault['mode']));

            return Command::SUCCESS;
        }

        if ($mode === 'off') {
            $this->faults->clear();
            $io->success('Fake ERP answers normally again');

            return Command::SUCCESS;
        }

        if (!in_array($mode, FakeErpFaults::MODES, true) || $times < 1) {
            $io->error(sprintf('Use one of %s, off or status, and --times >= 1', implode(', ', FakeErpFaults::MODES)));

            return Command::INVALID;
        }

        $this->faults->set($mode, $times);
        $io->success(sprintf('Fake ERP: the next %d call(s) fail with "%s"', $times, $mode));

        return Command::SUCCESS;
    }
}
