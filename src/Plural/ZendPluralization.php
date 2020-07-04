<?php

declare(strict_types=1);

namespace Ddrv\Translator\Plural;

use Ddrv\Translator\Contract\Pluralization;

final class ZendPluralization implements Pluralization
{

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
    public function definePosition(int $number, string $locale): int
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
}
