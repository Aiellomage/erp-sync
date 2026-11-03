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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Erp\Dto\ErpProductData;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class HttpErpClient implements ErpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface   $erpClient,
        #[Autowire(env: 'ERP_BASE_URL')]
        private readonly string                $baseUrl,
        private readonly DenormalizerInterface $denormalizer,
    )
    {
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
            throw new PermanentErpException('Invalid ERP request: ' . $e->getMessage(), previous: $e);
        } catch (TransportExceptionInterface $e) {
            throw new TransientErpException('ERP unreachable: ' . $e->getMessage(), previous: $e);
        } catch (ServerExceptionInterface $e) {
            throw new TransientErpException(sprintf('ERP server error %d', $e->getResponse()->getStatusCode()), previous: $e);
        } catch (ClientExceptionInterface $e) {
            $status = $e->getResponse()->getStatusCode();
            if ($status === 429) {
                throw new TransientErpException('ERP rate limit (429), retry later', previous: $e);
            }

            throw new PermanentErpException(sprintf('ERP rejected the request with %d', $status), previous: $e);
        } catch (DecodingExceptionInterface $e) {
            throw new PermanentErpException('ERP returned invalid JSON: ' . $e->getMessage(), previous: $e);
        }

        if (!isset($data['products']) || !is_array($data['products'])) {
            throw new PermanentErpException('ERP contract broken: "products" list missing');
        }

        $products = [];
        foreach ($data['products'] as $index => $p) {
            $products[] = $this->toProductData($p, $index);
        }

        return $products;
    }


    /**
     * A record becomes ErpProductData even if fields are missing: the Validator will reject it.
     * Only a record of the wrong type (not an object, a number instead of a string) breaks the contract.
     */
    private function toProductData(mixed $p, int|string $index): ErpProductData
    {
        if (!is_array($p)) {
            throw new PermanentErpException(sprintf('ERP contract broken: product #%s is not an object', $index));
        }

        try {
            return $this->denormalizer->denormalize($p, ErpProductData::class);
        } catch (SerializerExceptionInterface $e) {
            throw new PermanentErpException(sprintf('ERP contract broken: product #%s: %s', $index, $e->getMessage()), previous: $e);
        }
    }

}
