<?php

declare(strict_types=1);

namespace App\Erp\Exception;

/**
 * Any failure while talking to the ERP: always one of the two subclasses.
 */
abstract class ErpException extends \RuntimeException
{
}
