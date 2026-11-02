<?php
declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;
use DateTimeImmutable;

interface ErpClientInterface
{
    public function ping(): array;

    /**
     * Prodotti modificati dopo $since; tutti se $since è null.
     *
     * @return list<ErpProduct>
     */
    public function fetchProducts(?DateTimeImmutable $since = null): array;


}
