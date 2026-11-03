<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProductData;
use DateTimeImmutable;
use DateTimeZone;

class FakeErpClient implements ErpClientInterface
{
    public function ping(): array
    {
        return [
            'erp' => 'fake-erp',
            'status' => 'ok',
            'checked_at' => new DateTimeImmutable('now', new DateTimeZone('UTC'))->format(DATE_ATOM),
        ];
    }

    /**
     * @return list<ErpProductData>
     */
    public function fetchProducts(?DateTimeImmutable $since = null): array
    {
        $products = [
            $this->product('134', 'prodotto1', '23', '2026-10-01T09:00:00+00:00'),
            $this->product('135', 'prodotto2', '23', '2026-10-20T14:30:00+00:00'),
            $this->product('136', 'prodotto3', '23', '2026-11-01T18:15:00+00:00'),
        ];

        if ($since === null) {
            return $products;
        }

        // The raw date is a string: turned into a date only to compare it with $since
        return array_values(array_filter(
            $products,
            fn (ErpProductData $p) => new DateTimeImmutable($p->updatedAt) > $since,
        ));
    }

    private function product(string $sku, string $name, string $price, string $updatedAt): ErpProductData
    {
        $data = new ErpProductData();
        $data->sku = $sku;
        $data->name = $name;
        $data->price = $price;
        $data->updatedAt = $updatedAt;

        return $data;
    }
}
