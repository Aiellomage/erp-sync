<?php
declare(strict_types=1);
namespace App\Erp\Dto;

use DateTimeImmutable;

class ErpProduct
{

    public function __construct(
        public readonly string $sku,
        public readonly string $name,
        public readonly string $price,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

}
