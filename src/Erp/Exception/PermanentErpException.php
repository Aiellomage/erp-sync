<?php

declare(strict_types=1);

namespace App\Erp\Exception;

/**
 * Retrying will not help: wrong configuration, request rejected (4xx) or broken contract.
 */
final class PermanentErpException extends ErpException
{
}
