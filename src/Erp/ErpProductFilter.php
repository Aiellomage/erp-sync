<?php

declare(strict_types=1);

namespace App\Erp;

use App\Erp\Dto\ErpProduct;
use App\Erp\Dto\ErpProductData;
use App\Erp\Dto\FilteredProducts;
use App\Erp\Dto\RejectedProduct;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Policy B: a broken record is rejected and logged, the others go on.
 */
final readonly class ErpProductFilter
{
    public function __construct(
        private ValidatorInterface $validator,
        private LoggerInterface $erpLogger,
    ) {
    }

    /**
     * @param list<ErpProductData> $records
     */
    public function filter(array $records): FilteredProducts
    {
        $valid = [];
        $rejected = [];

        foreach ($records as $index => $data) {
            $violations = $this->validator->validate($data);

            if (count($violations) === 0) {
                // Validated: every field is there and well formed
                $valid[] = new ErpProduct($data->sku, $data->name, $data->price, new \DateTimeImmutable($data->updatedAt));
                continue;
            }

            $reasons = [];
            foreach ($violations as $violation) {
                $reasons[] = $violation->getPropertyPath().': '.$violation->getMessage();
            }
            $rejected[] = new RejectedProduct($index, $data->sku, $reasons);

            $this->erpLogger->warning('Rejected ERP product #{index} ({sku}): {reasons}', [
                'index' => $index,
                'sku' => $data->sku ?? 'no SKU',
                'reasons' => implode('; ', $reasons),
            ]);
        }

        return new FilteredProducts($valid, $rejected);
    }
}
