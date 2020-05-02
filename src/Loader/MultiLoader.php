<?php

declare(strict_types=1);

namespace Ddrv\Translator\Loader;

use Ddrv\Translator\Contract\TranslationLoader;

class MultiLoader implements TranslationLoader
{

    /**
     * @var TranslationLoader[]
     */
    private $loaders;

    public function __construct(TranslationLoader ...$loaders)
    {
        $this->loaders = $loaders;
    }

    public function addLoader(TranslationLoader $loader): self
    {
        $this->loaders[] = $loader;
        return $this;
    }

    public function load(string $domain, string $locale): array
    {
        foreach ($this->loaders as $loader) {
            $data = $loader->load($domain, $locale);
            if ($data) {
                return $data;
            }
        }
        return [];
    }
}
