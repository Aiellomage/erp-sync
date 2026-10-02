<?php
declare(strict_types=1);

namespace App\Erp;

use DateTimeImmutable;
use DateTimeZone;

class FakeErpClient implements ErpClientInterface
{


    public function ping(): array
    {

        return ['erp' => 'fake-erp',
            'status' => 'ok',
            'checked_at' => new DateTimeImmutable('now', new DateTimeZone('UTC'))->format(DATE_ATOM)];

    }
}
