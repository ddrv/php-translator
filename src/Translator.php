<?php

declare(strict_types=1);

namespace Ddrv\Translator;

use Ddrv\Translator\Contract\TranslationLoader;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function array_key_exists;
use function count;
use function explode;
use function is_array;
use function is_numeric;
use function strlen;
use function strpos;
use function strrpos;
use function substr;

final class Translator
{

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string[][][]
     */
    private $messages = [];

    /**
     * @var array[][][][]
     */
    private $plural = [];

    /**
     * @var TranslationLoader
     */
    private $loader;

    /**
     * @var CacheInterface|null
     */
    private $cache;

    public function __construct(string $defaultLocale, TranslationLoader $loader, ?CacheInterface $cache = null)
    {
        $this->setLocale($defaultLocale);
        $this->loader = $loader;
        $this->cache = $cache;
    }

    public function trans(string $id, array $parameters = [], string $locale = null): string
    {
        $locale = $locale ?: $this->getLocale();
        $arr = explode(':', $id);
        if (count($arr) === 2) {
            $domain = $arr[0];
            $key = $arr[1];
        } else {
            $domain = null;
            $key = $arr[0];
        }
        $this->load($domain ?? 'default', $locale);
        $template = $domain ? implode(':', [$domain, $key]) : $key;

        if (
            isset($parameters['count'])
            && is_numeric($parameters['count'])
            && !empty($this->plural[$locale][$domain][$key])
        ) {
            $number = (float)$parameters['count'];
            $rules = $this->plural[$locale][$domain][$key];
            $ok = false;
            foreach ($rules as $rule) {
                if ($ok) {
                    continue;
                }
                switch ($rule['type']) {
                    case 'standard':
                        if ($this->getPluralizationPosition((int)$number, $locale) === $rule['rule']) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                    case 'interval[]':
                        if ($number >= $rule['rule']['from'] && $number <= $rule['rule']['to']) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                    case 'interval()':
                        if ($number > $rule['rule']['from'] && $number < $rule['rule']['to']) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                    case 'interval[)':
                        if ($number >= $rule['rule']['from'] && $number < $rule['rule']['to']) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                    case 'interval(]':
                        if ($number > $rule['rule']['from'] && $number <= $rule['rule']['to']) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                    case 'values':
                        if (in_array($number, $rule['rule'])) {
                            $template = $rule['text'];
                            $ok = true;
                        }
                        break;
                }
            }
        } elseif (
            array_key_exists($locale, $this->messages)
            && array_key_exists($domain, $this->messages[$locale])
            && array_key_exists($key, $this->messages[$locale][$domain])
        ) {
            $template = $this->messages[$locale][$domain][$key];
        }
        return strtr($template, $this->wrapParameters($parameters));
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function load(string $domain, string $locale): void
    {
        if (array_key_exists($locale, $this->messages) && array_key_exists($domain, $this->messages[$locale])) {
            return;
        }
        $cacheKey = $domain . ':' . $locale;
        $data = [];
        if ($this->cache) {
            try {
                $data = $this->cache->get($cacheKey, []);
            } catch (InvalidArgumentException $e) {
            }
            if (!array_key_exists('messages', $data) && !array_key_exists('plural', $data)) {
                $data = [];
            }
            if ($data) {
                $this->messages[$locale][$domain] = $data['messages'];
                $this->plural[$locale][$domain] = $data['plural'];
                return;
            }
        }
        $data = $this->loader->load($domain, $locale);
        if (!is_array($data)) {
            return;
        }
        $messages = $this->walk($data);
        $plural = [];
        foreach ($messages as $key => $value) {
            if (strpos($value, '|') !== false) {
                $plural[$key] = $this->getPluralizationRule($value);
            }
        }
        $this->messages[$locale][$domain] = $messages;
        $this->plural[$locale][$domain] = $plural;
        if ($this->cache) {
            try {
                $this->cache->set($cacheKey, ['messages' => $messages, 'plural' => $plural], 86400);
            } catch (InvalidArgumentException $e) {
            }
        }
        return;
    }

    private function walk(array $array, string $key = '', array $result = []): array
    {
        foreach ($array as $k => $v) {
            $newKey = $key ? $key . '.' . $k : $k;
            if (!is_array($v)) {
                $result[$newKey] = $v;
            } else {
                $result = $this->walk($v, $newKey, $result);
            }
        }
        return $result;
    }

    private function getPluralizationRule(string $template): array
    {
        /**
         * The interval regexp are derived from code of the Symfony Translation component v4.4,
         * which is subject to the MIT license
         */
        $intervalRegexp = <<<REGEXP
/^(?P<interval>
    ({\s*
        (-?\d+(\.\d+)?(\s*,\s*-?\d+(.\d+)?)*)
    \s*})

        |

    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
REGEXP;
        $result = [];
        $empty = [
            'type' => 'standard',
            'rule' => null,
            'text' => null,
        ];
        $parts = explode('|', $template);
        $standard = 0;
        foreach ($parts as $part) {
            $rule = $empty;
            $rule['text'] = $part;
            if (preg_match($intervalRegexp, $part, $matches)) {
                $rule['text'] = $matches['message'];
                if ($matches[2]) {
                    $rule['type'] = 'values';
                    $values = [];
                    foreach (explode(',', $matches[3]) as $n) {
                        $values[] = (float)trim($n);
                    }
                    $rule['rule'] = $values;
                } else {
                    $type = ['interval', '(', ')'];
                    $rule['rule']['from'] = '-Inf' === $matches['left'] ? -INF : (float) $matches['left'];
                    $rule['rule']['to'] = is_numeric($matches['right']) ? (float) $matches['right'] : INF;
                    $type[1] = '[' === $matches['left_delimiter'] ? '[' : '(';
                    $type[2] = ']' === $matches['right_delimiter'] ? ']' : ')';
                    $rule['type'] = implode('', $type);
                }
            } else {
                $rule['rule'] = $standard;
                $standard++;
            }
            if ($rule['type'] === 'standard') {
                array_push($result, $rule);
            } else {
                array_unshift($result, $rule);
            }
        }
        return $result;
    }

    /**
     * Returns the plural position to use for the given locale and number.
     *
     * The plural rules are derived from code of the Zend Framework (2010-09-25),
     * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     *
     * @param int $number
     * @param string $locale
     * @return int
     */
    private function getPluralizationPosition(int $number, string $locale): int
    {
        switch ('pt_BR' !== $locale && strlen($locale) > 3 ? substr($locale, 0, strrpos($locale, '_')) : $locale) {
            case 'af':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'oc':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                return (1 == $number) ? 0 : 1;

            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'hy':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'pt_BR':
            case 'ti':
            case 'wa':
                return ((0 == $number) || (1 == $number)) ? 0 : 1;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sh':
            case 'sr':
            case 'uk':
                if ((1 == $number % 10) && (11 != $number % 100)) {
                    return 0;
                }
                if (($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) {
                    return 1;
                }
                return 2;

            case 'cs':
            case 'sk':
                return (1 == $number) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);

            case 'ga':
                return (1 == $number) ? 0 : ((2 == $number) ? 1 : 2);

            case 'lt':
                if ((1 == $number % 10) && (11 != $number % 100)) {
                    return 0;
                }
                return (($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2;

            case 'sl':
                if (1 == $number % 100) {
                    return 0;
                }
                return (2 == $number % 100) ? 1 : (((3 == $number % 100) || (4 == $number % 100)) ? 2 : 3);

            case 'mk':
                return (1 == $number % 10) ? 0 : 1;

            case 'mt':
                if (1 == $number) {
                    return 0;
                }
                if ((0 == $number) || (($number % 100 > 1) && ($number % 100 < 11))) {
                    return 1;
                }
                return (($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3;

            case 'lv':
                return (0 == $number) ? 0 : (((1 == $number % 10) && (11 != $number % 100)) ? 1 : 2);

            case 'pl':
                if (1 == $number) {
                    return 0;
                }
                if (($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) {
                    return 1;
                }
                return 2;

            case 'cy':
                return (1 == $number) ? 0 : ((2 == $number) ? 1 : (((8 == $number) || (11 == $number)) ? 2 : 3));

            case 'ro':
                return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);

            case 'ar':
                if (in_array($number, [0, 1, 2])) {
                    return $number;
                }
                if (($number % 100 >= 3) && ($number % 100 <= 10)) {
                    return 3;
                }
                return (($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5;

            default:
                return 0;
        }
    }

    private function wrapParameters(array $parameters): array
    {
        $result = [
            '%I%' => '|',
        ];
        foreach ($parameters as $key => $value) {
            $result['%' . $key . '%'] = $value;
        }
        return $result;
    }
}
