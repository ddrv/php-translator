<?php

declare(strict_types=1);

namespace Ddrv\Translator\Contract;

interface ParameterWrapper
{

    public function __invoke(string $name): string;
}
