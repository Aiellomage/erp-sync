<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ErpPinger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PingController extends AbstractController
{
    public function __construct(
        private readonly ErpPinger $erpPinger,
    ) {
    }

    #[Route('/api/ping', name: 'api_ping', methods: ['GET'])]
    public function ping(): Response
    {
        return $this->json($this->erpPinger->ping());
    }
}
