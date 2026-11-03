<?php
declare(strict_types=1);

namespace App\Erp\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ErpProductData
{
    #[Assert\NotBlank(message: 'SKU is required')]
    public ?string $sku = null;
    #[Assert\NotBlank(message: 'Name is required')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Price is required')]
    public ?string $price = null;

    #[SerializedName('updated_at')]
    #[Assert\NotBlank(message: 'Update date is required')]
    public ?string $updatedAt = null;

}
