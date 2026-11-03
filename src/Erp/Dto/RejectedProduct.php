<?php

declare(strict_types=1);

namespace App\Erp\Dto;

/**
 * A product from the ERP that did not pass validation, with the reasons.
 */
final readonly class RejectedProduct
{
    /**
     * @param list<string> $reasons e.g. ['price: Price "-10" must be a decimal >= 0']
     */
    public function __construct(
        public int $index,
        public ?string $sku,
        public array $reasons,
    ) {
    }
}

