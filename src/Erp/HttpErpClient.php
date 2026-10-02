<?php
declare(strict_types=1);

namespace App\Erp;

class HttpErpClient implements ErpClientInterface
{

    public function ping(): array
    {
        return ['erp' => 'http-erp', 'status' => 'ok'];
    }
}
