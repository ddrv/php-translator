<?php

declare(strict_types=1);

namespace Ddrv\Translator\Provider;

use Ddrv\Translator\Contract\DomainLoader;
use Ddrv\Translator\Contract\Pluralization;
use Ddrv\Translator\Contract\TranslationProvider as TranslationProviderContract;
use Ddrv\Translator\Plural\ZendPluralization;

use function array_key_exists;
use function array_push;
use function array_unshift;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function preg_match;
use function strpos;
use function trim;

final class TranslationProvider implements TranslationProviderContract
{

    /**
     * @var string[][][]
     */
    private $messages = [];

    /**
     * @var array
     */
    private $plural = [];

    /**
     * @var Pluralization
     */
    private $pluralization;

    /**
     * @var DomainLoader
     */
    private $domainLoader;

    public function __construct(DomainLoader $domainLoader, ?Pluralization $pluralization = null)
    {
        $this->domainLoader = $domainLoader;
        $this->pluralization = $pluralization ? $pluralization : new ZendPluralization();
    }

    final public function get(string $key, string $domain, string $locale, ?float $number): ?string
    {
        $this->loadDomain($domain, $locale);
        if (
            $number !== null
            && array_key_exists($domain, $this->plural)
            && array_key_exists($locale, $this->plural[$domain])
            && array_key_exists($key, $this->plural[$domain][$locale])
        ) {
            foreach ($this->plural[$domain][$locale][$key] as $rule) {
                switch ($rule['type']) {
                    case 'standard':
                        if ($this->pluralization->definePosition((int)$number, $locale) === $rule['rule']) {
                            return $rule['text'];
                        }
                        break;
                    case 'interval[]':
                        if ($number >= $rule['rule']['from'] && $number <= $rule['rule']['to']) {
                            return $rule['text'];
                        }
                        break;
                    case 'interval()':
                        if ($number > $rule['rule']['from'] && $number < $rule['rule']['to']) {
                            return $rule['text'];
                        }
                        break;
                    case 'interval[)':
                        if ($number >= $rule['rule']['from'] && $number < $rule['rule']['to']) {
                            return $rule['text'];
                        }
                        break;
                    case 'interval(]':
                        if ($number > $rule['rule']['from'] && $number <= $rule['rule']['to']) {
                            return $rule['text'];
                        }
                        break;
                    case 'values':
                        if (in_array($number, $rule['rule'])) {
                            return $rule['text'];
                        }
                        break;
                }
            }
        }
        if (
            array_key_exists($domain, $this->messages)
            && array_key_exists($locale, $this->messages[$domain])
            && array_key_exists($key, $this->messages[$domain][$locale])
        ) {
            return $this->messages[$domain][$locale][$key];
        }
        return null;
    }

    private function loadDomain(string $domain, $locale)
    {
        if (
            (array_key_exists($domain, $this->messages) && array_key_exists($locale, $this->messages[$domain]))
            || (array_key_exists($domain, $this->plural) && array_key_exists($locale, $this->plural[$domain]))
        ) {
            return;
        }
        $messages = $this->walk($this->domainLoader->domain($domain, $locale));
        $plural = [];
        foreach ($messages as $key => $value) {
            if (strpos($value, '|') !== false) {
                $plural[$key] = $this->getPluralizationRule($value);
            }
        }
        $this->messages[$domain][$locale] = $messages;
        $this->plural[$domain][$locale] = $plural;
    }

    private function walk(array $array, string $key = '', array $result = []): array
    {
        foreach ($array as $k => $v) {
            $newKey = $key ? $key . '.' . $k : $k;
            if (is_array($v)) {
                $result = $this->walk($v, $newKey, $result);
            } else {
                $result[$newKey] = $v;
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
}
