<?php

declare(strict_types=1);

namespace Ddrv\Translator\Loader;

use Ddrv\Translator\Contract\DomainLoader;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class CachedLoader implements DomainLoader
{

    /**
     * @var DomainLoader
     */
    private $loader;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $prefix;

    public function __construct(DomainLoader $loader, CacheInterface $cache, string $prefix = '')
    {
        $this->loader = $loader;
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function domain(string $domain, string $locale): array
    {
        $key = $this->prefix . $domain . '_' . $locale;
        try {
            $data = $this->cache->get($key, []);
        } catch (InvalidArgumentException $e) {
            $data = [];
        }
        if (empty($data)) {
            $data = $this->loader->domain($domain, $locale);
            try {
                $this->cache->set($key, $data);
            } catch (InvalidArgumentException $e) {
            }
        }
        return $data;
    }
}
