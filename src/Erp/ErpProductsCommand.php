<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Exception\ErpException;
use App\Message\ImportProductBatch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'app:erp:products', description: 'Lists the products from the ERP')]
class ErpProductsCommand
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly MessageBusInterface $bus,
        private readonly ErpProductFilter $filter,
    ) {
    }

    public function __invoke(SymfonyStyle $io,
        #[Option(description: 'Number of products per ImportProductBatch message')]
        int $batchSize = 100,
        #[Option(description: 'Dispatch the producs as an ImportproductBatch message')]
        bool $import = false): int
    {
        if ($batchSize < 1) {
            $io->error('--batch-size must be at least 1');

            return Command::INVALID;
        }

        try {
            $products = $this->erpClient->fetchProducts();
        } catch (ErpException $e) {
            $io->error(['Cannot read the products from the ERP.', $e->getMessage()]);

            return Command::FAILURE;
        }

        // Only valid products are listed and imported (they become ErpProduct)
        $filtered = $this->filter->filter($products);
        $products = $filtered->valid;
        if ([] !== $filtered->rejected) {
            $io->warning(sprintf('%d invalid products skipped (see var/log/erp.log)', count($filtered->rejected)));
        }

        $batches = array_chunk($products, $batchSize);

        foreach ($batches as $index => $batch) {
            $io->section(sprintf('Batch %d/%d', $index + 1, count($batches)));

            $rows = [];
            foreach ($batch as $product) {
                $rows[] = [$product->sku, $product->name, $product->price];
            }
            $io->table(['SKU', 'Name', 'Price'], $rows);

            if ($import) {
                $this->bus->dispatch(new ImportProductBatch($batch));
            }
        }

        if ($import) {
            $io->note(sprintf('%d ImportProductBatch dispatched', count($batches)));
        }

        $io->success(sprintf('%d products', count($products)));

        return Command::SUCCESS;
    }
}
