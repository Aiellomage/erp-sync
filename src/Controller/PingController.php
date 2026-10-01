<?php

declare(strict_types=1);

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ErpPinger;

class PingController extends AbstractController
{
    private ErpPinger $erpPinger;

    public function __construct(ErpPinger $erpPinger)
    {
        $this->erpPinger = $erpPinger;
    }

    #[Route('/api/ping', name: 'api_ping', methods: ['GET'])]
    public function ping(): Response
    {
        $pinger = $this->erpPinger->ping();
        return $this->json($pinger);
    }
}
