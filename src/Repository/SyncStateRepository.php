<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SyncState>
 */
class SyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SyncState::class);
    }

    public function findLastRunAt(string $name): ?\DateTimeImmutable
    {
        return $this->find($name)?->getLastRunAt();
    }

    public function saveLastRunAt(string $name, \DateTimeImmutable $lastRunAt): void
    {
        $state = $this->find($name);
        if (null === $state) {
            $state = new SyncState($name, $lastRunAt);
            $this->getEntityManager()->persist($state);
        } else {
            $state->setLastRunAt($lastRunAt);
        }

        $this->getEntityManager()->flush();
    }
}
