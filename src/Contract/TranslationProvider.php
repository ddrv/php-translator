<?php

declare(strict_types=1);

namespace Ddrv\Translator\Contract;

interface TranslationProvider
{

    public function get(string $key, string $domain, string $locale, ?float $number): ?string;
}
