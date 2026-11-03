<?php

declare(strict_types=1);

namespace App\Tests\Erp;

use App\Erp\Dto\ErpProductData;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('prices')]
    public function testThePriceMustBeAPositiveDecimal(string $price, ?string $expectedError): void
    {
        $product = new ErpProductData();
        $product->sku = 'TSHIRT-001';
        $product->name = 'White T-shirt';
        $product->price = $price;
        $product->updatedAt = '2026-10-01T09:00:00+00:00';

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($product);

        if ($expectedError === null) {
            self::assertCount(0, $violations, (string) $violations);

            return;
        }
        self::assertCount(1, $violations);
        self::assertSame($expectedError, $violations[0]->getMessage());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function prices(): iterable
    {
        yield 'decimal' => ['19.90', null];
        yield 'integer' => ['59', null];
        yield 'one decimal' => ['12.5', null];
        yield 'negative' => ['-10', 'Price "-10" must be a decimal >= 0'];
        yield 'comma' => ['19,90', 'Price "19,90" must be a decimal >= 0'];
        yield 'three decimals' => ['19.999', 'Price "19.999" must be a decimal >= 0'];
    }

    #[DataProvider('skus')]
    public function testTheSkuMustBeUppercaseLettersDigitsAndDashes(string $sku, ?string $expectedError): void
    {
        $product = new ErpProductData();
        $product->sku = $sku;
        $product->name = 'White T-shirt';
        $product->price = '19.90';
        $product->updatedAt = '2026-10-01T09:00:00+00:00';

        $violations = self::getContainer()->get(ValidatorInterface::class)->validate($product);

        if ($expectedError === null) {
            self::assertCount(0, $violations, (string) $violations);

            return;
        }
        self::assertCount(1, $violations, (string) $violations);
        self::assertSame($expectedError, $violations[0]->getMessage());
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function skus(): iterable
    {
        yield 'valid' => ['TSHIRT-001', null];
        yield 'digits only' => ['12345', null];
        yield 'lowercase' => ['tshirt-001', 'SKU "tshirt-001" has invalid characters'];
        yield 'space' => ['TSHIRT 001', 'SKU "TSHIRT 001" has invalid characters'];
        yield 'too long' => [str_repeat('A', 65), 'This value is too long. It should have 64 characters or less.'];
    }
}
