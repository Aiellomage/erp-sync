<?php

declare(strict_types=1);

namespace App\Tests\Erp;

use App\Erp\Exception\PermanentErpException;
use App\Erp\Exception\TransientErpException;
use App\Erp\HttpErpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Unit test: no server, no network. MockHttpClient plays the ERP and returns
 * the responses we prepare, so every error case is fast and repeatable.
 */
final class HttpErpClientTest extends TestCase
{
    private const BASE_URI = 'https://erp.test/api/';

    public function testItTurnsTheJsonIntoErpProducts(): void
    {
        $client = $this->clientReturning(new MockResponse(json_encode(['products' => [
            ['sku' => 'TSHIRT-001', 'name' => 'White T-shirt', 'price' => '19.90', 'updated_at' => '2026-10-01T09:00:00+00:00'],
            ['sku' => 'CAP-001', 'name' => 'Cap', 'price' => '12.50', 'updated_at' => '2026-11-02T18:15:00+00:00'],
        ]])));

        $products = $client->fetchProducts();

        self::assertCount(2, $products);
        self::assertSame('TSHIRT-001', $products[0]->sku);
        self::assertSame('19.90', $products[0]->price);
        self::assertSame('2026-11-02T18:15:00+00:00', $products[1]->updatedAt->format(DATE_ATOM));
    }

    public function testItSendsSinceAsAQueryParameter(): void
    {
        $response = new MockResponse(json_encode(['products' => []]));
        $client = $this->clientReturning($response);

        $client->fetchProducts(new \DateTimeImmutable('2026-10-20T00:00:00+00:00'));

        // The mock remembers the request: we check what HttpErpClient really asked for.
        // The "+" of the offset must be encoded as %2B, otherwise the ERP would read a space.
        self::assertSame('GET', $response->getRequestMethod());
        self::assertSame('https://erp.test/api/catalog/products?since=2026-10-20T00:00:00%2B00:00', $response->getRequestUrl());
    }

    /**
     * @param class-string<\Throwable> $expected
     */
    #[DataProvider('failures')]
    public function testItClassifiesTheErpFailures(MockResponse $response, string $expected): void
    {
        $client = $this->clientReturning($response);

        $this->expectException($expected);

        $client->fetchProducts();
    }

    /**
     * @return iterable<string, array{MockResponse, class-string<\Throwable>}>
     */
    public static function failures(): iterable
    {
        yield 'network error' => [new MockResponse(info: ['error' => 'Connection refused']), TransientErpException::class];
        yield '500' => [new MockResponse('', ['http_code' => 500]), TransientErpException::class];
        yield '503' => [new MockResponse('', ['http_code' => 503]), TransientErpException::class];
        yield '429' => [new MockResponse('', ['http_code' => 429]), TransientErpException::class];
        yield '400' => [new MockResponse('', ['http_code' => 400]), PermanentErpException::class];
        yield '404' => [new MockResponse('', ['http_code' => 404]), PermanentErpException::class];
        yield 'invalid JSON' => [new MockResponse('{"products": ['), PermanentErpException::class];
        yield 'no products list' => [new MockResponse('{"items": []}'), PermanentErpException::class];
        yield 'missing price' => [new MockResponse(json_encode(['products' => [
            ['sku' => 'TSHIRT-002', 'name' => 'Black T-shirt', 'updated_at' => '2026-10-15T09:00:00+00:00'],
        ]])), PermanentErpException::class];
        yield 'invalid date' => [new MockResponse(json_encode(['products' => [
            ['sku' => 'TSHIRT-002', 'name' => 'Black T-shirt', 'price' => '19.90', 'updated_at' => 'yesterday-ish'],
        ]])), PermanentErpException::class];
    }

    private function clientReturning(MockResponse $response): HttpErpClient
    {
        return new HttpErpClient(new MockHttpClient($response, self::BASE_URI), self::BASE_URI);
    }
}
