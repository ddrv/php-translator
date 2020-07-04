<?php

declare(strict_types=1);

namespace Ddrv\Translator\Loader;

use Ddrv\Translator\Contract\DomainLoader;

use function array_replace;

final class MultiLoader implements DomainLoader
{

    /**
     * @var DomainLoader[]
     */
    private $loaders;

    public function __construct(DomainLoader $loader)
    {
        $this->loaders[] = $loader;
    }

    public function addLoader(DomainLoader $loader): self
    {
        foreach ($this->loaders as $item) {
            if ($loader === $item) {
                return $this;
            }
        }
        $this->loaders[] = $loader;
        return $this;
    }

    public function domain(string $domain, string $locale): array
    {
        $result = [];
        foreach ($this->loaders as $loader) {
            $result = array_replace($result, $loader->domain($domain, $locale));
        }
        return $result;
    }
}
