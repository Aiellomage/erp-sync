<?php
declare(strict_types=1);
namespace App\Erp;

interface ErpClientInterface
{
    public function ping(): array;

}
