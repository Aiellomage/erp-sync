<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param list<string> $skus
     *
     * @return array<string, Product>
     */
    public function findBySkus(array $skus): array
    {
        if ([] === $skus) {
            return [];
        }

        return $this->createQueryBuilder('p', 'p.sku')
            ->where('p.sku IN (:skus)')
            ->setParameter('skus', $skus)
            ->getQuery()->getResult();
    }
}
