<?php

declare(strict_types=1);

namespace Ddrv\Translator\Loader;

use Ddrv\Translator\Contract\TranslationLoader;

use function file_exists;
use function is_readable;

class FileLoader implements TranslationLoader
{

    /**
     * @var string
     */
    private $dir;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    public function load(string $domain, string $locale): array
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

    private function getFileName(string $domain, string $locale): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->dir, $domain, $locale]) . '.php';
    }
}
