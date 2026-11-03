<?php

declare(strict_types=1);

namespace App\Tests\Erp;

use App\Erp\Dto\ErpProduct;
use App\Erp\Dto\ErpProductData;
use App\Erp\ErpProductFilter;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Policy B with the real container: broken records are rejected with their reasons, the others go on.
 */
final class ErpProductFilterTest extends KernelTestCase
{
    public function testItKeepsTheValidProductsAndRejectsTheBrokenOnes(): void
    {
        // The same six products as the fake ERP: four valid, no SKU, negative price
        $records = [
            $this->record('TSHIRT-001', 'White T-shirt', '19.90', '2026-10-01T09:00:00+00:00'),
            $this->record('TSHIRT-002', 'Black T-shirt', '19.90', '2026-10-15T09:00:00+00:00'),
            $this->record('', 'Product without SKU', '9.90', '2026-10-20T10:00:00+00:00'),
            $this->record('JEANS-001', 'Slim jeans', '59.00', '2026-10-25T14:30:00+00:00'),
            $this->record('SOCKS-001', 'Socks', '-10', '2026-10-28T08:00:00+00:00'),
            $this->record('CAP-001', 'Cap', '12.50', '2026-11-02T18:15:00+00:00'),
        ];

        $result = $this->filter()->filter($records);

        // Valid: they became ErpProduct, with a real date
        self::assertSame(['TSHIRT-001', 'TSHIRT-002', 'JEANS-001', 'CAP-001'], array_map(fn (ErpProduct $p) => $p->sku, $result->valid));
        self::assertSame('2026-11-02T18:15:00+00:00', $result->valid[3]->updatedAt->format(DATE_ATOM));

        // Rejected: which one, and why
        self::assertCount(2, $result->rejected);
        self::assertSame(2, $result->rejected[0]->index);
        self::assertSame(['sku: SKU is required'], $result->rejected[0]->reasons);
        self::assertSame('SOCKS-001', $result->rejected[1]->sku);
        self::assertSame(['price: Price "-10" must be a decimal >= 0'], $result->rejected[1]->reasons);
    }

    public function testAMissingFieldIsARejectionNotAnException(): void
    {
        $noPrice = new ErpProductData();
        $noPrice->sku = 'TSHIRT-002';
        $noPrice->name = 'Black T-shirt';
        $noPrice->updatedAt = '2026-10-15T09:00:00+00:00';

        $result = $this->filter()->filter([$noPrice]);

        self::assertSame([], $result->valid);
        self::assertSame(['price: Price is required'], $result->rejected[0]->reasons);
    }

    private function filter(): ErpProductFilter
    {
        // Built by hand: the real Validator from the container, a logger that writes nothing
        return new ErpProductFilter(self::getContainer()->get(ValidatorInterface::class), new NullLogger());
    }

    private function record(string $sku, string $name, string $price, string $updatedAt): ErpProductData
    {
        $data = new ErpProductData();
        $data->sku = $sku;
        $data->name = $name;
        $data->price = $price;
        $data->updatedAt = $updatedAt;

        return $data;
    }
}
