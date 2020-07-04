<?php

declare(strict_types=1);

namespace Ddrv\Translator\Contract;

interface Pluralization
{

    public function definePosition(int $number, string $locale): int;
}
