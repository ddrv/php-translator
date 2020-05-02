<?php

declare(strict_types=1);

namespace Ddrv\Tests\Translator;

use Ddrv\Translator\Contract\TranslationLoader;
use Ddrv\Translator\Loader\FileLoader;
use Ddrv\Translator\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{

    /**
     * @param string $locale
     * @param string $key
     * @param array $params
     * @param string $expected
     *
     * @dataProvider provideTranslate
     */
    public function testTranslate(?string $locale, string $key, array $params, string $expected)
    {
        $translator = new Translator('en_US', $this->getLoader());
        $this->assertSame($expected, $translator->trans($key, $params, $locale));
    }

    public function provideTranslate(): array
    {
        return [
            ['en_US', 'default:test', ['package' => 'ddrv/translator'], 'it is ddrv/translator package'],
            ['en_US', 'phpunit:text.hello', ['user' => 'PHPUnit'], 'Hello, PHPUnit!'],
            ['en_US', 'phpunit:text.hello', ['user' => 'PHPUnit'], 'Hello, PHPUnit!'],
            ['en_US', 'phpunit:text.bye', ['user' => 'PHPUnit'], 'Bye!'],
            ['en_US', 'phpunit:text.bye', [], 'Bye!'],
            ['en_US', 'phpunit:number.comments', ['count' => 0], 'No comments'],
            ['en_US', 'phpunit:number.comments', ['count' => 1], '1 comment'],
            ['en_US', 'phpunit:number.comments', ['count' => 2], '2 comments'],
            ['en_US', 'phpunit:number.comments', ['count' => 5], '5 comments'],
            ['en_US', 'phpunit:interval.quantity', ['count' => -INF], 'error'],
            ['en_US', 'phpunit:interval.quantity', ['count' => -1], 'error'],
            ['en_US', 'phpunit:interval.quantity', ['count' => 0], 'empty'],
            ['en_US', 'phpunit:interval.quantity', ['count' => 2], 'less than 10'],
            ['en_US', 'phpunit:interval.quantity', ['count' => 11], 'a lot of'],
            ['en_US', 'phpunit:interval.quantity', ['count' => INF], 'a lot of'],
            ['ru_RU', 'default:test', ['package' => 'ddrv/translator'], 'это пакет ddrv/translator'],
            ['ru_RU', 'phpunit:text.hello', ['user' => 'PHPUnit'], 'Привет, PHPUnit!'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 0], 'Комментариев нет'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 1], '1 комментарий'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 2], '2 комментария'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 5], '5 комментариев'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 10], '10 комментариев'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 11], '11 комментариев'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 21], '21 комментарий'],
            ['ru_RU', 'phpunit:number.comments', ['count' => 105], '105 комментариев'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => -INF], 'ошибка'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => -1], 'ошибка'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => 0], 'отсутствует'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => 2], 'менее 10'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => 11], 'много'],
            ['ru_RU', 'phpunit:interval.quantity', ['count' => INF], 'много'],
            ['fr_FR', 'phpunit:text.hello', ['user' => 'PHPUnit'], 'phpunit:text.hello'],
            [null, 'Undefined %string%', ['string' => 'key'], 'Undefined key'],
            [null, 'Undefined %string%', [], 'Undefined %string%'],
            [null, 'phpunit:text.hello', ['user' => 'PHPUnit'], 'Hello, PHPUnit!'],
            [null, 'text with %I% separator', [], 'text with | separator'],
        ];
    }


    private function getLoader(): TranslationLoader
    {
        return new FileLoader(implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'stuff', 'i18n']));
    }
}
