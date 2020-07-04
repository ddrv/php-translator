<?php

declare(strict_types=1);

namespace Ddrv\Translator;

use Ddrv\Translator\Contract\ParameterWrapper;
use Ddrv\Translator\Contract\TranslationProvider;
use Ddrv\Translator\ParameterWrapper\SymfonyStyle;

use function array_key_exists;
use function count;
use function explode;
use function is_numeric;
use function strtr;

final class Translator
{

    /**
     * @var string
     */
    private $locale;

    /**
     * @var TranslationProvider
     */
    private $provider;

    /**
     * @var ParameterWrapper
     */
    private $parameterWrapper;

    public function __construct(string $defaultLocale, TranslationProvider $loader, ?ParameterWrapper $wrapper = null)
    {
        $this->setLocale($defaultLocale);
        $this->provider = $loader;
        $this->parameterWrapper = $wrapper ? $wrapper : new SymfonyStyle();
    }

    public function trans(string $key, array $parameters = [], string $locale = null): string
    {
        $locale = $locale ?: $this->getLocale();
        $arr = explode(':', $key, 2);
        if (count($arr) === 2) {
            $domain = $arr[0];
            $id = $arr[1];
        } else {
            $domain = 'default';
            $id = $arr[0];
        }

        $number = null;
        if (array_key_exists('count', $parameters) && is_numeric($parameters['count'])) {
            $number = $parameters['count'];
        }
        $template = $this->provider->get($id, $domain, $locale, $number);
        if (!$template) {
            $template = $key;
        }
        return $this->replace($template, $parameters);
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    private function replace(string $template, array $source): string
    {
        $fn = $this->parameterWrapper;
        if (!array_key_exists('I', $source)) {
            $source['I'] = '|';
        }
        $params = [];
        foreach ($source as $key => $value) {
            $params[$fn((string)$key)] = $value;
        }
        return strtr($template, $params);
    }
}
