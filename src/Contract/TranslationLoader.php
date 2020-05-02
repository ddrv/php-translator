<?php

declare(strict_types=1);

namespace Ddrv\Translator\Contract;

interface TranslationLoader
{

    public function load(string $domain, string $locale): array;
}
