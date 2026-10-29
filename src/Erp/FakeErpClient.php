<?php
declare(strict_types=1);

namespace App\Erp;

use DateTimeImmutable;
use DateTimeZone;
use App\Erp\Dto\ErpProduct;
class FakeErpClient implements ErpClientInterface
{


    public function ping(): array
    {

        return ['erp' => 'fake-erp',
            'status' => 'ok',
            'checked_at' => new DateTimeImmutable('now', new DateTimeZone('UTC'))->format(DATE_ATOM)];

    }
    /**
     * @return list<ErpProduct>
     */
    public function fetchProducts(): array
    {
        $prodotto1 = new ErpProduct("134","prodotto1","23");
        $prodotto2 = new ErpProduct("135","prodotto2","23");
        $prodotto3 = new ErpProduct("136","prodotto3","23");
        return [$prodotto1, $prodotto2, $prodotto3];
    }
}
