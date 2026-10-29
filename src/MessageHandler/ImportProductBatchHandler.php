<?php
declare(strict_types=1);
namespace App\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Message\ImportProductBatch;
use Psr\Log\LoggerInterface;
#[AsMessageHandler]

final readonly class ImportProductBatchHandler
{

    public function __construct(private LoggerInterface $erpLogger){}

    public function __invoke( ImportProductBatch $message): void
    {

        $this->erpLogger->info('ImportProductBatch handled: {count} products', [
            'count' => count($message->products),
        ]);

    }

}
