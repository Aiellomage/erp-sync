<?php

declare(strict_types=1);

namespace App\Erp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Attribute\Option;
use App\Message\ImportProductBatch;

#[AsCommand(name: 'app:erp:products', description: 'Lists the products from the ERP')]
class ErpProductsCommand
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(SymfonyStyle $io,
    #[Option(description: 'Dispatch the producs as an ImportproductBatch message')]
    bool $import= false): int
    {

        $products = $this->erpClient->fetchProducts();

        $rows = [];
        foreach ($products as $product) {
            $rows[] = [$product->sku, $product->name, $product->price];
        }

        $io->table(['SKU', 'Name', 'Price'], $rows);
        if ($import) {
            $this->bus->dispatch(new ImportProductBatch($products));
            $io->note('ImportProductBatch dispatched');
        }

        $io->success(sprintf('%d products', count($products)));

        return Command::SUCCESS;
    }
}
