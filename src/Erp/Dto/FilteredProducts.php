<?php

declare(strict_types=1);

namespace App\Erp\Dto;

/**
 * What ErpProductFilter returns: the products that can go on, and the ones it rejected.
 */
final readonly class FilteredProducts
{
    /**
     * @param list<ErpProduct>      $valid
     * @param list<RejectedProduct> $rejected
     */
    public function __construct(
        public array $valid,
        public array $rejected,
    ) {
    }
}
