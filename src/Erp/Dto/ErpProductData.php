<?php
declare(strict_types=1);

namespace App\Erp\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ErpProductData
{
    #[Assert\NotBlank(message: 'SKU is required')]
    #[Assert\Length(max: 64)]
    #[Assert\Regex('/^[A-Z0-9-]+$/', message: 'SKU {{ value }} has invalid characters')]
    public ?string $sku = null;


    #[Assert\NotBlank(message: 'Name is required')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Price is required')]
    #[Assert\Regex('/^\d+(\.\d{1,2})?$/', message: 'Price {{ value }} must be a decimal >= 0')]
    public ?string $price = null;

    #[SerializedName('updated_at')]
    #[Assert\NotBlank(message: 'Update date is required')]
    #[Assert\DateTime(format: \DATE_ATOM, message: 'Update date {{ value }} is not a valid date')]
    public ?string $updatedAt = null;

}
