<?php
declare(strict_types=1);
namespace App\Erp\Dto;

class ErpProduct
{

    public function __construct(public readonly string $sku,public readonly string $name, public readonly string $price)
    {
    }

}
