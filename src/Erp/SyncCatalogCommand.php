<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Exception\PermanentErpException;
use App\Erp\Exception\TransientErpException;
use App\Message\ImportProductBatch;
use App\Repository\SyncStateRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:sync:catalog', description: 'Dispatches the ERP products changed since a date as ImportProductBatch messages')]
class SyncCatalogCommand
{
    private const SYNC_NAME = 'catalog';

    /** sysexits.h EX_TEMPFAIL: temporary failure, the caller (cron, supervisor) may retry later */
    public const EXIT_TEMPFAIL = 75;

    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly MessageBusInterface $bus,
        private readonly SyncStateRepository $syncState,
    ) {
    }

    public function __invoke(SymfonyStyle $io,
        #[Option(description: 'Only products changed after this date (e.g. "2026-10-15" or "-2 days"), UTC; default: last successful run')]
        ?string $since = null,
        #[Option(description: 'Ignore the last successful run and sync the whole catalog')]
        bool $full = false,
        #[Option(description: 'Number of products per ImportProductBatch message')]
        int $batchSize = 100,
        #[Option(description: 'Show what would be dispatched without dispatching it')]
        bool $dryRun = false): int
    {
        if ($batchSize < 1) {
            $io->error('--batch-size must be at least 1');

            return Command::INVALID;
        }

        if ($full && null !== $since) {
            $io->error('--full and --since cannot be used together');

            return Command::INVALID;
        }

        try {
            $sinceDate = null === $since ? null : new \DateTimeImmutable($since, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            $io->error(sprintf('--since "%s" is not a valid date', $since));

            return Command::INVALID;
        }

        if (null === $sinceDate && !$full) {
            $sinceDate = $this->syncState->findLastRunAt(self::SYNC_NAME);
        }

        // Taken before fetching: products changed while the sync runs are picked up next time.
        $startedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $io->title(null === $sinceDate
            ? 'Full catalog sync'
            : sprintf('Catalog sync since %s', $sinceDate->format(DATE_ATOM)));

        // The ERP client only says what happened; here we decide what to do about it.
        // The last run date is not saved on failure, so the next run asks for the same products.
        try {
            $products = $this->erpClient->fetchProducts($sinceDate);
        } catch (TransientErpException $e) {
            $io->warning(['ERP temporarily unavailable, nothing synced.', $e->getMessage(), 'Last run date unchanged: the next run retries the same products.']);

            return self::EXIT_TEMPFAIL;
        } catch (PermanentErpException $e) {
            $io->error(['ERP sync failed and will not recover by itself.', $e->getMessage()]);

            return Command::FAILURE;
        }
        if ([] === $products) {
            if (!$dryRun) {
                $this->syncState->saveLastRunAt(self::SYNC_NAME, $startedAt);
            }
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

        $this->syncState->saveLastRunAt(self::SYNC_NAME, $startedAt);
        $io->note(sprintf('Next run will sync products changed after %s', $startedAt->format(DATE_ATOM)));

        $io->success(sprintf('%d products dispatched in %d ImportProductBatch', count($products), count($batches)));

        return Command::SUCCESS;
    }
}
