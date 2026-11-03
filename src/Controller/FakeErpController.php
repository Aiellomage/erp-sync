<?php

declare(strict_types=1);

namespace App\Controller;

use App\FakeErp\FakeErpFaults;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[When(env: 'dev')]
final class FakeErpController extends AbstractController
{
    public function __construct(
        private readonly FakeErpFaults $faults,
    ) {
    }

    #[Route('/fake-erp/catalog/products', methods: ['GET'], env: 'dev')]
    public function products(Request $request): Response
    {
        $since = $request->query->get('since');
        $sinceDate = $since !== null ? new \DateTimeImmutable($since) : null;

        // ?fail= for quick tests with curl; otherwise the fault set with app:fake-erp:fail
        $fail = $request->query->get('fail') ?? $this->faults->next();
        $error = match ($fail) {
            '500'     => $this->json(['error' => 'Internal server error'], 500),
            '503'     => $this->json(['error' => 'ERP temporarily unavailable'], 503),
            '429'     => $this->json(['error' => 'Too many requests'], 429, ['Retry-After' => '2']),
            '400'     => $this->json(['error' => 'Bad request'], 400),
            'badjson' => new Response('{"products": [', 200, ['Content-Type' => 'application/json']),
            // Valid JSON, broken contract: the second product has no price
            'missingfield' => $this->json(['products' => [
                ['sku' => 'TSHIRT-001', 'name' => 'White T-shirt', 'price' => '19.90', 'updated_at' => '2026-10-01T09:00:00+00:00'],
                ['sku' => 'TSHIRT-002', 'name' => 'Black T-shirt', 'updated_at' => '2026-10-15T09:00:00+00:00'],
            ]]),
            // Valid JSON, broken contract: the list is called "items" instead of "products"
            'wrongkey' => $this->json(['items' => []]),
            default   => null,
        };

        if ($error !== null) {
            return $error;
        }

        if ($fail === 'slow') {
            sleep(5);
        }

        // Two invalid products on purpose (empty SKU, negative price) for the Validator
        $products = [
            ['sku' => 'TSHIRT-001', 'name' => 'White T-shirt', 'price' => '19.90', 'updated_at' => '2026-10-01T09:00:00+00:00'],
            ['sku' => 'TSHIRT-002', 'name' => 'Black T-shirt', 'price' => '19.90', 'updated_at' => '2026-10-15T09:00:00+00:00'],
            ['sku' => '', 'name' => 'Product without SKU', 'price' => '9.90', 'updated_at' => '2026-10-20T10:00:00+00:00'],
            ['sku' => 'JEANS-001', 'name' => 'Slim jeans', 'price' => '59.00', 'updated_at' => '2026-10-25T14:30:00+00:00'],
            ['sku' => 'SOCKS-001', 'name' => 'Socks', 'price' => '-10', 'updated_at' => '2026-10-28T08:00:00+00:00'],
            ['sku' => 'CAP-001', 'name' => 'Cap', 'price' => '12.50', 'updated_at' => '2026-11-02T18:15:00+00:00'],
        ];

        if ($sinceDate !== null) {
            $products = array_values(array_filter(
                $products,
                fn (array $p) => new \DateTimeImmutable($p['updated_at']) > $sinceDate,
            ));
        }

        return $this->json(['products' => $products]);
    }
}
