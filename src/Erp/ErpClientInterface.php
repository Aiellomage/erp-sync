<?php
declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;

interface ErpClientInterface
{
    public function ping(): array;

    /** @return list<ErpProduct> */
    public function fetchProducts(): array;


}
