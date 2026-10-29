<?php

declare(strict_types=1);

namespace App\Erp;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:erp:products', description: 'Lists the products from the ERP')]
class ErpProductsCommand
{
    public function __construct(
        private readonly ErpClientInterface $erpClient,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $products = $this->erpClient->fetchProducts();

        $rows = [];
        foreach ($products as $product) {
            $rows[] = [$product->sku, $product->name, $product->price];
        }

        $io->table(['SKU', 'Name', 'Price'], $rows);
        $io->success(sprintf('%d products', count($products)));

        return Command::SUCCESS;
    }
}
