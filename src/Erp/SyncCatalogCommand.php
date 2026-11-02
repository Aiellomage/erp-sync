<?php

declare(strict_types=1);

namespace App\Erp;

use App\Message\ImportProductBatch;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:sync:catalog', description: 'Dispatches the ERP products changed since a date as ImportProductBatch messages')]
class SyncCatalogCommand
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(SymfonyStyle $io,
        #[Option(description: 'Only products changed after this date (e.g. "2026-10-15" or "-2 days"), UTC')]
        ?string $since = null,
        #[Option(description: 'Number of products per ImportProductBatch message')]
        int $batchSize = 100,
        #[Option(description: 'Show what would be dispatched without dispatching it')]
        bool $dryRun = false): int
    {
        if ($batchSize < 1) {
            $io->error('--batch-size must be at least 1');

            return Command::INVALID;
        }

        try {
            $sinceDate = $since === null ? null : new DateTimeImmutable($since, new DateTimeZone('UTC'));
        } catch (\Exception) {
            $io->error(sprintf('--since "%s" is not a valid date', $since));

            return Command::INVALID;
        }

        $io->title($sinceDate === null
            ? 'Full catalog sync'
            : sprintf('Catalog sync since %s', $sinceDate->format(DATE_ATOM)));

        $products = $this->erpClient->fetchProducts($sinceDate);
        if ($products === []) {
            $io->success('Nothing to sync');

            return Command::SUCCESS;
        }

        $batches = array_chunk($products, $batchSize);

        if ($dryRun) {
            $rows = [];
            foreach ($products as $product) {
                $rows[] = [$product->sku, $product->name, $product->price, $product->updatedAt->format(DATE_ATOM)];
            }
            $io->table(['SKU', 'Name', 'Price', 'Updated at'], $rows);
            $io->note(sprintf('Dry run: %d products in %d batches, nothing dispatched', count($products), count($batches)));

            return Command::SUCCESS;
        }

        $io->progressStart(count($products));
        foreach ($batches as $batch) {
            $this->bus->dispatch(new ImportProductBatch($batch));
            $io->progressAdvance(count($batch));
        }
        $io->progressFinish();

        $io->success(sprintf('%d products dispatched in %d ImportProductBatch', count($products), count($batches)));

        return Command::SUCCESS;
    }
}
