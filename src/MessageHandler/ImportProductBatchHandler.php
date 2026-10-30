<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Product;
use App\Erp\Dto\ErpProduct;
use App\Message\ImportProductBatch;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ImportProductBatchHandler
{
    public function __construct(
        private LoggerInterface $erpLogger,
        private ProductRepository $products,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(ImportProductBatch $message): void
    {
        $skus = array_map(fn (ErpProduct $p) => $p->sku, $message->products);
        $existing = $this->products->findBySkus($skus);

        $now = new \DateTimeImmutable();
        $created = 0;
        $updated = 0;

        foreach ($message->products as $erp) {
            if (isset($existing[$erp->sku])) {
                $product = $existing[$erp->sku];
                ++$updated;
            } else {
                $product = new Product();
                $product->setSku($erp->sku);
                $this->em->persist($product);
                ++$created;
            }

            $product->setName($erp->name);
            $product->setPrice($erp->price);
            $product->setUpdatedAt($now);
        }

        $this->em->flush();
        $this->em->clear();

        $this->erpLogger->info('ImportProductBatch: {created} created, {updated} updated', [
            'created' => $created,
            'updated' => $updated,
        ]);
    }
}
