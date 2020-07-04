<?php

declare(strict_types=1);

namespace Ddrv\Translator\Contract;

interface DomainLoader
{

    public function domain(string $domain, string $locale): array;
}
