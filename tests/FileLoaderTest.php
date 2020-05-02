<?php

declare(strict_types=1);

namespace Ddrv\Tests\Translator;

use Ddrv\Translator\Contract\TranslationLoader;
use Ddrv\Translator\Loader\FileLoader;
use PHPUnit\Framework\TestCase;

class FileLoaderTest extends TestCase
{

    /**
     * @param string $dir
     *
     * @dataProvider provideConstruct
     */
    public function testConstruct(string $dir)
    {
        $loader = new FileLoader($dir);
        $this->assertInstanceOf(TranslationLoader::class, $loader);
    }

    /**
     * @param string $dir
     * @param string $domain
     * @param string $locale
     * @param bool   $isEmpty
     *
     * @dataProvider provideLoad
     */
    public function testLoad(string $dir, string $domain, string $locale, bool $isEmpty)
    {
        $loader = new FileLoader($dir);
        $data = $loader->load($domain, $locale);
        $this->assertSame($isEmpty, empty($data));
    }

    public function provideConstruct(): array
    {
        return [
            [$this->getCorrectDirectory()],
            [$this->getIncorrectDirectory()],
        ];
    }

    public function provideLoad(): array
    {
        return [
            [$this->getCorrectDirectory(), 'phpunit', 'en_US', false],
            [$this->getCorrectDirectory(), 'phpunit', 'ru_RU', false],
            [$this->getCorrectDirectory(), 'phpunit', 'fr_FR', true],
            [$this->getIncorrectDirectory(), 'phpunit', 'en_US', true],
            [$this->getIncorrectDirectory(), 'phpunit', 'ru_RU', true],
        ];
    }

    private function getCorrectDirectory(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stuff' . DIRECTORY_SEPARATOR . 'i18n';
    }

    private function getIncorrectDirectory(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'stuff' . DIRECTORY_SEPARATOR . 'not-existent';
    }
}
