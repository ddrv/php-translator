# ddrv/translator

[![Latest Stable Version](https://img.shields.io/packagist/v/ddrv/translator.svg?style=flat-square)](https://packagist.org/packages/ddrv/translator)
[![Total Downloads](https://img.shields.io/packagist/dt/ddrv/translator.svg?style=flat-square)](https://packagist.org/packages/ddrv/translator/stats)
[![License](https://img.shields.io/packagist/l/ddrv/translator.svg?style=flat-square)](https://github.com/ddrv/php-translator/blob/master/LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/ddrv/translator.svg?style=flat-square)](https://php.net)


PHP library for localization application.

# Install

1. Run in terminal:
    ```text
    composer require ddrv/translator:~1.0
    ```

1. Include autoload file
    ```php
    require_once 'vendor/autoload.php';
    ```

# Usage

1. Prepare translations

```php
<?php
// /path/to/translations/default/en_US.php

return [
    'test' => 'It is test!',
    'hello' => 'Hello, %name%',
    'count' => '%count% element|%count% elements|{0}no elements',
    'interval' => '[-Inf,0[negative|[0,Inf]positive',
    'multi' => [
        'level' => [
            'key' => 'It is key in multilevel array.',
        ],
    ],
];
```

```php
<?php
// /path/to/translations/default/ru_RU.php

return [
    'test' => 'Это тест!',
    'hello' => 'Привет, %name%',
    'count' => '%count% элемент|%count% элемента|%count% элементов|{0}элементов нет',
    'interval' => '[-Inf,0[отрицательное|[0,Inf]положительное',
    'multi' => [
        'level' => [
            'key' => 'Это ключ в многоуровневом массиве.',
        ],
    ],
];
```

1. Init library
```php
<?php

use Ddrv\Translator\Loader\FileLoader;
use Ddrv\Translator\Translator;

// Create a loader instance. 
$loader = new FileLoader('/path/to/translations');

/** @var Psr\SimpleCache\CacheInterface|null $cache */
$translator = new Translator('en_US', $loader, $cache);

```

1. Use it!

```php
<?php

use Ddrv\Translator\Translator;
/** @var Translator $translator */
$translator->trans('default:test'); // It is test!
$translator->trans('default:multi.level.key'); // It is key in multilevel array.
$translator->trans('default:test', [], 'ru_RU'); // Это тест!

$translator->setLocale('ru_RU');
$translator->trans('default:test'); // Это тест!
$translator->trans('default:multi.level.key'); // Это ключ в многоуровневом массиве.
```

1. Parameters

```php
<?php

use Ddrv\Translator\Translator;
/** @var Translator $translator */
$translator->trans('default:hello', ['name' => 'Ivan']); // Hello, Ivan!
$translator->trans('default:hello', ['name' => 'John']); // Hello, John!
```

1. Pluralization

```php
<?php

use Ddrv\Translator\Translator;
/** @var Translator $translator */
$translator->trans('default:count', ['count' => 1]); // 1 element!
$translator->trans('default:count', ['count' => 2]); // 2 elements!

$translator->trans('default:interval', ['count' => -2]); // negative
$translator->trans('default:interval', ['count' => 2]); // positive
```

You can develop your loader that implements the `\Ddrv\Translator\Contract\TranslationLoader` interface.

You can use multiple loaders at once

```php
<?php

use Ddrv\Translator\Contract\TranslationLoader;
use Ddrv\Translator\Loader\MultiLoader;
use Ddrv\Translator\Translator;

/**
 * @var TranslationLoader $loader1
 * @var TranslationLoader $loader2
 * @var TranslationLoader $loader3
 */
$loader = new MultiLoader($loader1, $loader2);
$loader->addLoader($loader3);

$translator = new Translator('en_US', $loader);
```
