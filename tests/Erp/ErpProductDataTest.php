<?php

declare(strict_types=1);

namespace App\Tests\Erp;

use App\Erp\Dto\ErpProductData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Serializer + Validator from the real container: JSON → ErpProductData → violations.
 */
final class ErpProductDataTest extends KernelTestCase
{
    public function testAProductWithoutPriceIsCreatedAndThenRejectedByTheValidator(): void
    {
        $json = '{"sku": "TSHIRT-002", "name": "Black T-shirt", "updated_at": "2026-10-15T09:00:00+00:00"}';

        // 1. Serializer: the object is created even if "price" is missing
        $product = self::getContainer()->get(SerializerInterface::class)->deserialize($json, ErpProductData::class, 'json');

        self::assertSame('TSHIRT-002', $product->sku);
        self::assertSame('2026-10-15T09:00:00+00:00', $product->updatedAt, 'updated_at → updatedAt via #[SerializedName]');
        self::assertNull($product->price);

        // 2. Validator: it finds the missing price, no exception
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($product);

        self::assertCount(1, $violations);
        self::assertSame('price', $violations[0]->getPropertyPath());
        self::assertSame('Price is required', $violations[0]->getMessage());
    }
}
