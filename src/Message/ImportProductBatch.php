<?php
declare(strict_types=1);
namespace App\Message;
use App\Erp\Dto\ErpProduct;


final readonly class ImportProductBatch
{
/**
 * @param list<ErpProduct> $products
 */
    public function __construct(
        public   array $products,
    )
    {
    }
}
