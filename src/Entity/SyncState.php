<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SyncStateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Last successful run of a sync, one row per sync name (e.g. "catalog").
 */
#[ORM\Entity(repositoryClass: SyncStateRepository::class)]
class SyncState
{
    #[ORM\Id]
    #[ORM\Column(length: 64)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $lastRunAt;

    public function __construct(string $name, \DateTimeImmutable $lastRunAt)
    {
        $this->name = $name;
        $this->lastRunAt = $lastRunAt;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastRunAt(): \DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(\DateTimeImmutable $lastRunAt): static
    {
        $this->lastRunAt = $lastRunAt;

        return $this;
    }
}
