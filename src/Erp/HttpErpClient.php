<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;
use App\Erp\Exception\PermanentErpException;
use App\Erp\Exception\TransientErpException;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpErpClient implements ErpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $erpClient,
        #[Autowire(env: 'ERP_BASE_URL')]
        private readonly string $baseUrl,
    ) {
    }

    public function ping(): array
    {
        return ['erp' => 'http-erp', 'status' => 'ok', 'base_url' => $this->baseUrl];
    }

    /** @return list<ErpProduct> */
    public function fetchProducts(?DateTimeImmutable $since = null): array
    {
        $options = [];
        if ($since !== null) {
            $options['query'] = ['since' => $since->format(DATE_ATOM)];
        }

        // request() is lazy: network and HTTP errors are thrown by toArray(), so both go in the try
        try {
            $response = $this->erpClient->request('GET', 'catalog/products', $options);
            $data = $response->toArray();
        } catch (InvalidArgumentException $e) {
            // Bad URL or option in this request. It implements TransportExceptionInterface too:
            // caught first because it is not transient. (A bad base_uri fails earlier, when the
            // container builds erp.client.)
            throw new PermanentErpException('Invalid ERP request: '.$e->getMessage(), previous: $e);
        } catch (TransportExceptionInterface $e) {
            throw new TransientErpException('ERP unreachable: '.$e->getMessage(), previous: $e);
        } catch (ServerExceptionInterface $e) {
            throw new TransientErpException(sprintf('ERP server error %d', $e->getResponse()->getStatusCode()), previous: $e);
        } catch (ClientExceptionInterface $e) {
            $status = $e->getResponse()->getStatusCode();
            if ($status === 429) {
                throw new TransientErpException('ERP rate limit (429), retry later', previous: $e);
            }

            throw new PermanentErpException(sprintf('ERP rejected the request with %d', $status), previous: $e);
        } catch (DecodingExceptionInterface $e) {
            throw new PermanentErpException('ERP returned invalid JSON: '.$e->getMessage(), previous: $e);
        }

        if (!isset($data['products']) || !is_array($data['products'])) {
            throw new PermanentErpException('ERP contract broken: "products" list missing');
        }

        $products = [];
        foreach ($data['products'] as $index => $p) {
            $products[] = $this->toProduct($p, $index);
        }

        return $products;
    }

    /**
     * Checks the shape of one product (the contract), not its business rules: those are the Validator's job.
     */
    private function toProduct(mixed $p, int|string $index): ErpProduct
    {
        foreach (['sku', 'name', 'price', 'updated_at'] as $field) {
            if (!is_array($p) || !array_key_exists($field, $p) || !is_string($p[$field])) {
                throw new PermanentErpException(sprintf('ERP contract broken: product #%s has no valid "%s"', $index, $field));
            }
        }

        try {
            $updatedAt = new DateTimeImmutable($p['updated_at']);
        } catch (\Exception $e) {
            throw new PermanentErpException(sprintf('ERP contract broken: product #%s has an invalid "updated_at" ("%s")', $index, $p['updated_at']), previous: $e);
        }

        return new ErpProduct($p['sku'], $p['name'], $p['price'], $updatedAt);
    }
}
