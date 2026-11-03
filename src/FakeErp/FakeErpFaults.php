<?php

declare(strict_types=1);

namespace App\FakeErp;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Fault injection for the fake ERP: the next N calls fail in the chosen way.
 * Kept in the app cache so the console command and the web controller share it.
 */
#[When(env: 'dev')]
final readonly class FakeErpFaults
{
    public const MODES = ['500', '503', '429', '400', 'slow', 'badjson', 'missingfield', 'wrongkey'];

    private const KEY = 'fake_erp.fault';

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function set(string $mode, int $times): void
    {
        $item = $this->cache->getItem(self::KEY);
        $this->cache->save($item->set(['mode' => $mode, 'remaining' => $times]));
    }

    public function clear(): void
    {
        $this->cache->deleteItem(self::KEY);
    }

    /** @return array{mode: string, remaining: int}|null */
    public function current(): ?array
    {
        $item = $this->cache->getItem(self::KEY);

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * The fault for this call (and one less left), or null when the ERP should answer normally.
     */
    public function next(): ?string
    {
        $fault = $this->current();
        if ($fault === null) {
            return null;
        }

        if (--$fault['remaining'] > 0) {
            $this->set($fault['mode'], $fault['remaining']);
        } else {
            $this->clear();
        }

        return $fault['mode'];
    }
}
