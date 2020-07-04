<?php

declare(strict_types=1);

namespace Ddrv\Translator\Loader;

use Ddrv\Translator\Contract\DomainLoader;

use function file_exists;
use function implode;
use function is_array;
use function is_readable;

class FileLoader implements DomainLoader
{

    /**
     * @var string
     */
    private $dir;

    /**
     * @var string
     */
    protected $extension;

    public function __construct(string $dir, string $extension = '.php')
    {
        $this->dir = $dir;
        $this->extension = $extension;
    }

    final public function domain(string $domain, string $locale): array
    {
        $file = $this->getFileName($domain, $locale);

        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }
        /** @noinspection PhpIncludeInspection */
        $data = include $file;
        if (!is_array($data)) {
            return [];
        }
        return $data;
    }

    protected function getFileName(string $domain, string $locale): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->dir, $domain, $locale]) . $this->extension;
    }
}
