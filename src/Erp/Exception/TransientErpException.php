<?php

declare(strict_types=1);

namespace App\Erp\Exception;

/**
 * The ERP may answer if we try again later (timeout, 5xx, 429, network down).
 */
final class TransientErpException extends ErpException
{
}
