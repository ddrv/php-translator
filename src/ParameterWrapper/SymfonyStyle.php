<?php

declare(strict_types=1);

namespace Ddrv\Translator\ParameterWrapper;

use Ddrv\Translator\Contract\ParameterWrapper;

final class SymfonyStyle implements ParameterWrapper
{

    public function __invoke(string $name): string
    {
        return '%' . $name . '%';
    }
}
