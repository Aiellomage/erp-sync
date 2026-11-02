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
    public function fetchProducts(?DateTimeImmutable $since = null): array
    {
        $utc = new DateTimeZone('UTC');
        $prodotto1 = new ErpProduct("134","prodotto1","23", new DateTimeImmutable('2026-10-01 09:00:00', $utc));
        $prodotto2 = new ErpProduct("135","prodotto2","23", new DateTimeImmutable('2026-10-20 14:30:00', $utc));
        $prodotto3 = new ErpProduct("136","prodotto3","23", new DateTimeImmutable('2026-11-01 18:15:00', $utc));
        $products = [$prodotto1, $prodotto2, $prodotto3];

        if ($since === null) {
            return $products;
        }

        return array_values(array_filter($products, fn (ErpProduct $p) => $p->updatedAt > $since));
    }
}
