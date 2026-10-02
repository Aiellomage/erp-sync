<?php

declare(strict_types=1);

namespace App\Service;

use App\Erp\ErpClientInterface;

class ErpPinger
{


    public function __construct(private readonly ErpClientInterface $client)
    {
    }

    public function ping(): array
    {
        return  $this->client->ping();
        }
}
