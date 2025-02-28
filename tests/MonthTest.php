<?php

namespace MeridiemTests;

use Meridiem\Month;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Month::class)]
class MonthTest extends TestCase
{
    /** All months. */
    public static function months(): iterable
    {
        foreach (Month::cases() as $month) {
            yield $month->name => [$month];
        }
    }

    /** Each month that has months before it paired with each of the months before it. */
    public static function monthsAndEarlierMonths(): iterable
    {
        yield 'february-january' => [Month::February, Month::January];

        yield 'march-january' => [Month::March, Month::January];
        yield 'march-february' => [Month::March, Month::February];

        yield 'april-january' => [Month::April, Month::January];
        yield 'april-february' => [Month::April, Month::February];
        yield 'april-march' => [Month::April, Month::March];

        yield 'may-january' => [Month::May, Month::January];
        yield 'may-february' => [Month::May, Month::February];
        yield 'may-march' => [Month::May, Month::March];
        yield 'may-april' => [Month::May, Month::April];

        yield 'june-january' => [Month::June, Month::January];
        yield 'june-february' => [Month::June, Month::February];
        yield 'june-march' => [Month::June, Month::March];
        yield 'june-april' => [Month::June, Month::April];
        yield 'june-may' => [Month::June, Month::May];

        yield 'july-january' => [Month::July, Month::January];
        yield 'july-february' => [Month::July, Month::February];
        yield 'july-march' => [Month::July, Month::March];
        yield 'july-april' => [Month::July, Month::April];
        yield 'july-may' => [Month::July, Month::May];
        yield 'july-june' => [Month::July, Month::June];

        yield 'august-january' => [Month::August, Month::January];
        yield 'august-february' => [Month::August, Month::February];
        yield 'august-march' => [Month::August, Month::March];
        yield 'august-april' => [Month::August, Month::April];
        yield 'august-may' => [Month::August, Month::May];
        yield 'august-june' => [Month::August, Month::June];
        yield 'august-july' => [Month::August, Month::July];

        yield 'september-january' => [Month::September, Month::January];
        yield 'september-february' => [Month::September, Month::February];
        yield 'september-march' => [Month::September, Month::March];
        yield 'september-april' => [Month::September, Month::April];
        yield 'september-may' => [Month::September, Month::May];
        yield 'september-june' => [Month::September, Month::June];
        yield 'september-july' => [Month::September, Month::July];
        yield 'september-august' => [Month::September, Month::August];

        yield 'october-january' => [Month::October, Month::January];
        yield 'october-february' => [Month::October, Month::February];
        yield 'october-march' => [Month::October, Month::March];
        yield 'october-april' => [Month::October, Month::April];
        yield 'october-may' => [Month::October, Month::May];
        yield 'october-june' => [Month::October, Month::June];
        yield 'october-july' => [Month::October, Month::July];
        yield 'october-august' => [Month::October, Month::August];
        yield 'october-september' => [Month::October, Month::September];

        yield 'november-january' => [Month::November, Month::January];
        yield 'november-february' => [Month::November, Month::February];
        yield 'november-march' => [Month::November, Month::March];
        yield 'november-april' => [Month::November, Month::April];
        yield 'november-may' => [Month::November, Month::May];
        yield 'november-june' => [Month::November, Month::June];
        yield 'november-july' => [Month::November, Month::July];
        yield 'november-august' => [Month::November, Month::August];
        yield 'november-september' => [Month::November, Month::September];
        yield 'november-october' => [Month::November, Month::October];

        yield 'december-january' => [Month::December, Month::January];
        yield 'december-february' => [Month::December, Month::February];
        yield 'december-march' => [Month::December, Month::March];
        yield 'december-april' => [Month::December, Month::April];
        yield 'december-may' => [Month::December, Month::May];
        yield 'december-june' => [Month::December, Month::June];
        yield 'december-july' => [Month::December, Month::July];
        yield 'december-august' => [Month::December, Month::August];
        yield 'december-september' => [Month::December, Month::September];
        yield 'december-october' => [Month::December, Month::October];
        yield 'december-november' => [Month::December, Month::November];
    }

    /** Each month that has months after it paired with each of the months after it. */
    public static function monthsAndLaterMonths(): iterable
    {
        yield 'january-february' => [Month::January, Month::February];
        yield 'january-march' => [Month::January, Month::March];
        yield 'january-april' => [Month::January, Month::April];
        yield 'january-may' => [Month::January, Month::May];
        yield 'january-june' => [Month::January, Month::June];
        yield 'january-july' => [Month::January, Month::July];
        yield 'january-august' => [Month::January, Month::August];
        yield 'january-september' => [Month::January, Month::September];
        yield 'january-october' => [Month::January, Month::October];
        yield 'january-november' => [Month::January, Month::November];
        yield 'january-december' => [Month::January, Month::December];

        yield 'february-march' => [Month::February, Month::March];
        yield 'february-april' => [Month::February, Month::April];
        yield 'february-may' => [Month::February, Month::May];
        yield 'february-june' => [Month::February, Month::June];
        yield 'february-july' => [Month::February, Month::July];
        yield 'february-august' => [Month::February, Month::August];
        yield 'february-september' => [Month::February, Month::September];
        yield 'february-october' => [Month::February, Month::October];
        yield 'february-november' => [Month::February, Month::November];
        yield 'february-december' => [Month::February, Month::December];

        yield 'march-april' => [Month::March, Month::April];
        yield 'march-may' => [Month::March, Month::May];
        yield 'march-june' => [Month::March, Month::June];
        yield 'march-july' => [Month::March, Month::July];
        yield 'march-august' => [Month::March, Month::August];
        yield 'march-september' => [Month::March, Month::September];
        yield 'march-october' => [Month::March, Month::October];
        yield 'march-november' => [Month::March, Month::November];
        yield 'march-december' => [Month::March, Month::December];

        yield 'april-may' => [Month::April, Month::May];
        yield 'april-june' => [Month::April, Month::June];
        yield 'april-july' => [Month::April, Month::July];
        yield 'april-august' => [Month::April, Month::August];
        yield 'april-september' => [Month::April, Month::September];
        yield 'april-october' => [Month::April, Month::October];
        yield 'april-november' => [Month::April, Month::November];
        yield 'april-december' => [Month::April, Month::December];

        yield 'may-june' => [Month::May, Month::June];
        yield 'may-july' => [Month::May, Month::July];
        yield 'may-august' => [Month::May, Month::August];
        yield 'may-september' => [Month::May, Month::September];
        yield 'may-october' => [Month::May, Month::October];
        yield 'may-november' => [Month::May, Month::November];
        yield 'may-december' => [Month::May, Month::December];

        yield 'june-july' => [Month::June, Month::July];
        yield 'june-august' => [Month::June, Month::August];
        yield 'june-september' => [Month::June, Month::September];
        yield 'june-october' => [Month::June, Month::October];
        yield 'june-november' => [Month::June, Month::November];
        yield 'june-december' => [Month::June, Month::December];

        yield 'july-august' => [Month::July, Month::August];
        yield 'july-september' => [Month::July, Month::September];
        yield 'july-october' => [Month::July, Month::October];
        yield 'july-november' => [Month::July, Month::November];
        yield 'july-december' => [Month::July, Month::December];

        yield 'august-september' => [Month::August, Month::September];
        yield 'august-october' => [Month::August, Month::October];
        yield 'august-november' => [Month::August, Month::November];
        yield 'august-december' => [Month::August, Month::December];

        yield 'september-october' => [Month::September, Month::October];
        yield 'september-november' => [Month::September, Month::November];
        yield 'september-december' => [Month::September, Month::December];

        yield 'october-november' => [Month::October, Month::November];
        yield 'october-december' => [Month::October, Month::December];

        yield 'november-december' => [Month::November, Month::December];
    }

    /** The first 6 multiples of 12. */
    public static function multiplesOfTwelve(): iterable
    {
        for ($factor = 0; $factor < 5; ++$factor) {
            $multiple = 12 * $factor;
            yield (string) $multiple => [$multiple];
        }
    }

    /** Each Month paired with the Month following it. */
    public static function monthsAdvancedOne(): iterable
    {
        yield "january-advanced-one" => [Month::January, Month::February];
        yield "february-advanced-one" => [Month::February, Month::March];
        yield "march-advanced-one" => [Month::March, Month::April];
        yield "april-advanced-one" => [Month::April, Month::May];
        yield "may-advanced-one" => [Month::May, Month::June];
        yield "june-advanced-one" => [Month::June, Month::July];
        yield "july-advanced-one" => [Month::July, Month::August];
        yield "august-advanced-one" => [Month::August, Month::September];
        yield "september-advanced-one" => [Month::September, Month::October];
        yield "october-advanced-one" => [Month::October, Month::November];
        yield "november-advanced-one" => [Month::November, Month::December];
        yield "december-advanced-one" => [Month::December, Month::January];
    }

    /** Each Month paired with the Month 2 months after it. */
    public static function monthsAdvancedTwo(): iterable
    {
        yield "january-advanced-two" => [Month::January, Month::March];
        yield "february-advanced-two" => [Month::February, Month::April];
        yield "march-advanced-two" => [Month::March, Month::May];
        yield "april-advanced-two" => [Month::April, Month::June];
        yield "may-advanced-two" => [Month::May, Month::July];
        yield "june-advanced-two" => [Month::June, Month::August];
        yield "july-advanced-two" => [Month::July, Month::September];
        yield "august-advanced-two" => [Month::August, Month::October];
        yield "september-advanced-two" => [Month::September, Month::November];
        yield "october-advanced-two" => [Month::October, Month::December];
        yield "november-advanced-two" => [Month::November, Month::January];
        yield "december-advanced-two" => [Month::December, Month::February];
    }

    /** Each Month paired with the Month 3 months after it. */
    public static function monthsAdvancedThree(): iterable
    {
        yield "january-advanced-three" => [Month::January, Month::April];
        yield "february-advanced-three" => [Month::February, Month::May];
        yield "march-advanced-three" => [Month::March, Month::June];
        yield "april-advanced-three" => [Month::April, Month::July];
        yield "may-advanced-three" => [Month::May, Month::August];
        yield "june-advanced-three" => [Month::June, Month::September];
        yield "july-advanced-three" => [Month::July, Month::October];
        yield "august-advanced-three" => [Month::August, Month::November];
        yield "september-advanced-three" => [Month::September, Month::December];
        yield "october-advanced-three" => [Month::October, Month::January];
        yield "november-advanced-three" => [Month::November, Month::February];
        yield "december-advanced-three" => [Month::December, Month::March];
    }

    /** Each Month paired with the Month 4 months after it. */
    public static function monthsAdvancedFour(): iterable
    {
        yield "january-advanced-four" => [Month::January, Month::May];
        yield "february-advanced-four" => [Month::February, Month::June];
        yield "march-advanced-four" => [Month::March, Month::July];
        yield "april-advanced-four" => [Month::April, Month::August];
        yield "may-advanced-four" => [Month::May, Month::September];
        yield "june-advanced-four" => [Month::June, Month::October];
        yield "july-advanced-four" => [Month::July, Month::November];
        yield "august-advanced-four" => [Month::August, Month::December];
        yield "september-advanced-four" => [Month::September, Month::January];
        yield "october-advanced-four" => [Month::October, Month::February];
        yield "november-advanced-four" => [Month::November, Month::March];
        yield "december-advanced-four" => [Month::December, Month::April];
    }

    /** Each Month paired with the Month 5 months after it. */
    public static function monthsAdvancedFive(): iterable
    {
        yield "january-advanced-five" => [Month::January, Month::June];
        yield "february-advanced-five" => [Month::February, Month::July];
        yield "march-advanced-five" => [Month::March, Month::August];
        yield "april-advanced-five" => [Month::April, Month::September];
        yield "may-advanced-five" => [Month::May, Month::October];
        yield "june-advanced-five" => [Month::June, Month::November];
        yield "july-advanced-five" => [Month::July, Month::December];
        yield "august-advanced-five" => [Month::August, Month::January];
        yield "september-advanced-five" => [Month::September, Month::February];
        yield "october-advanced-five" => [Month::October, Month::March];
        yield "november-advanced-five" => [Month::November, Month::April];
        yield "december-advanced-five" => [Month::December, Month::May];
    }

    /** Each Month paired with the Month 6 months after it. */
    public static function monthsAdvancedSix(): iterable
    {
        yield "january-advanced-six" => [Month::January, Month::July];
        yield "february-advanced-six" => [Month::February, Month::August];
        yield "march-advanced-six" => [Month::March, Month::September];
        yield "april-advanced-six" => [Month::April, Month::October];
        yield "may-advanced-six" => [Month::May, Month::November];
        yield "june-advanced-six" => [Month::June, Month::December];
        yield "july-advanced-six" => [Month::July, Month::January];
        yield "august-advanced-six" => [Month::August, Month::February];
        yield "september-advanced-six" => [Month::September, Month::March];
        yield "october-advanced-six" => [Month::October, Month::April];
        yield "november-advanced-six" => [Month::November, Month::May];
        yield "december-advanced-six" => [Month::December, Month::June];
    }

    /** Each Month paired with the Month 7 months after it. */
    public static function monthsAdvancedSeven(): iterable
    {
        yield "january-advanced-seven" => [Month::January, Month::August];
        yield "february-advanced-seven" => [Month::February, Month::September];
        yield "march-advanced-seven" => [Month::March, Month::October];
        yield "april-advanced-seven" => [Month::April, Month::November];
        yield "may-advanced-seven" => [Month::May, Month::December];
        yield "june-advanced-seven" => [Month::June, Month::January];
        yield "july-advanced-seven" => [Month::July, Month::February];
        yield "august-advanced-seven" => [Month::August, Month::March];
        yield "september-advanced-seven" => [Month::September, Month::April];
        yield "october-advanced-seven" => [Month::October, Month::May];
        yield "november-advanced-seven" => [Month::November, Month::June];
        yield "december-advanced-seven" => [Month::December, Month::July];
    }

    /** Each Month paired with the Month 8 months after it. */
    public static function monthsAdvancedEight(): iterable
    {
        yield "january-advanced-eight" => [Month::January, Month::September];
        yield "february-advanced-eight" => [Month::February, Month::October];
        yield "march-advanced-eight" => [Month::March, Month::November];
        yield "april-advanced-eight" => [Month::April, Month::December];
        yield "may-advanced-eight" => [Month::May, Month::January];
        yield "june-advanced-eight" => [Month::June, Month::February];
        yield "july-advanced-eight" => [Month::July, Month::March];
        yield "august-advanced-eight" => [Month::August, Month::April];
        yield "september-advanced-eight" => [Month::September, Month::May];
        yield "october-advanced-eight" => [Month::October, Month::June];
        yield "november-advanced-eight" => [Month::November, Month::July];
        yield "december-advanced-eight" => [Month::December, Month::August];
    }

    /** Each Month paired with the Month 9 months after it. */
    public static function monthsAdvancedNine(): iterable
    {
        yield "january-advanced-nine" => [Month::January, Month::October];
        yield "february-advanced-nine" => [Month::February, Month::November];
        yield "march-advanced-nine" => [Month::March, Month::December];
        yield "april-advanced-nine" => [Month::April, Month::January];
        yield "may-advanced-nine" => [Month::May, Month::February];
        yield "june-advanced-nine" => [Month::June, Month::March];
        yield "july-advanced-nine" => [Month::July, Month::April];
        yield "august-advanced-nine" => [Month::August, Month::May];
        yield "september-advanced-nine" => [Month::September, Month::June];
        yield "october-advanced-nine" => [Month::October, Month::July];
        yield "november-advanced-nine" => [Month::November, Month::August];
        yield "december-advanced-nine" => [Month::December, Month::September];
    }

    /** Each Month paired with the Month 10 months after it. */
    public static function monthsAdvancedTen(): iterable
    {
        yield "january-advanced-ten" => [Month::January, Month::November];
        yield "february-advanced-ten" => [Month::February, Month::December];
        yield "march-advanced-ten" => [Month::March, Month::January];
        yield "april-advanced-ten" => [Month::April, Month::February];
        yield "may-advanced-ten" => [Month::May, Month::March];
        yield "june-advanced-ten" => [Month::June, Month::April];
        yield "july-advanced-ten" => [Month::July, Month::May];
        yield "august-advanced-ten" => [Month::August, Month::June];
        yield "september-advanced-ten" => [Month::September, Month::July];
        yield "october-advanced-ten" => [Month::October, Month::August];
        yield "november-advanced-ten" => [Month::November, Month::September];
        yield "december-advanced-ten" => [Month::December, Month::October];
    }

    /** Each Month paired with the Month 11 months after it. */
    public static function monthsAdvancedEleven(): iterable
    {
        yield "january-advanced-eleven" => [Month::January, Month::December];
        yield "february-advanced-eleven" => [Month::February, Month::January];
        yield "march-advanced-eleven" => [Month::March, Month::February];
        yield "april-advanced-eleven" => [Month::April, Month::March];
        yield "may-advanced-eleven" => [Month::May, Month::April];
        yield "june-advanced-eleven" => [Month::June, Month::May];
        yield "july-advanced-eleven" => [Month::July, Month::June];
        yield "august-advanced-eleven" => [Month::August, Month::July];
        yield "september-advanced-eleven" => [Month::September, Month::August];
        yield "october-advanced-eleven" => [Month::October, Month::September];
        yield "november-advanced-eleven" => [Month::November, Month::October];
        yield "december-advanced-eleven" => [Month::December, Month::November];
    }

    /** Each Month paired with the Month preceding it. */
    public static function monthsBackOne(): iterable
    {
        foreach (self::monthsAdvancedEleven() as $key => $months) {
            yield str_replace("advanced-eleven", "back-one", $key) => $months;
        }
    }

    /** Each Month paired with the Month 2 months before it. */
    public static function monthsBackTwo(): iterable
    {
        foreach (self::monthsAdvancedTen() as $key => $months) {
            yield str_replace("advanced-ten", "back-two", $key) => $months;
        }
    }

    /** Each Month paired with the Month 3 months before it. */
    public static function monthsBackThree(): iterable
    {
        foreach (self::monthsAdvancedNine() as $key => $months) {
            yield str_replace("advanced-nine", "back-three", $key) => $months;
        }
    }

    /** Each Month paired with the Month 4 months before it. */
    public static function monthsBackFour(): iterable
    {
        foreach (self::monthsAdvancedEight() as $key => $months) {
            yield str_replace("advanced-eight", "back-four", $key) => $months;
        }
    }

    /** Each Month paired with the Month 5 months before it. */
    public static function monthsBackFive(): iterable
    {
        foreach (self::monthsAdvancedSeven() as $key => $months) {
            yield str_replace("advanced-seven", "back-five", $key) => $months;
        }
    }

    /** Each Month paired with the Month 6 months before it. */
    public static function monthsBackSix(): iterable
    {
        foreach (self::monthsAdvancedSix() as $key => $months) {
            yield str_replace("advanced-six", "back-six", $key) => $months;
        }
    }

    /** Each Month paired with the Month 7 months before it. */
    public static function monthsBackSeven(): iterable
    {
        foreach (self::monthsAdvancedFive() as $key => $months) {
            yield str_replace("advanced-five", "back-seven", $key) => $months;
        }
    }

    /** Each Month paired with the Month 8 months before it. */
    public static function monthsBackEight(): iterable
    {
        foreach (self::monthsAdvancedFour() as $key => $months) {
            yield str_replace("advanced-four", "back-eight", $key) => $months;
        }
    }

    /** Each Month paired with the Month 9 months before it. */
    public static function monthsBackNine(): iterable
    {
        foreach (self::monthsAdvancedThree() as $key => $months) {
            yield str_replace("advanced-three", "back-nine", $key) => $months;
        }
    }

    /** Each Month paired with the Month 10 months before it. */
    public static function monthsBackTen(): iterable
    {
        foreach (self::monthsAdvancedTwo() as $key => $months) {
            yield str_replace("advanced-two", "back-ten", $key) => $months;
        }
    }

    /** Each Month paired with the Month 11 months before it. */
    public static function monthsBackEleven(): iterable
    {
        foreach (self::monthsAdvancedOne() as $key => $months) {
            yield str_replace("advanced-one", "back-eleven", $key) => $months;
        }
    }

    /** All months advanced by between 1 and 11 months, 13 and 23 months, 25 and 35 months, ... */
    public static function monthsAdvanced(): iterable
    {
        foreach (self::multiplesOfTwelve() as $multipleKey => $multiple) {
            $multiple = $multiple[0];

            // all months advanced 1, 13, 25, ...
            foreach (self::monthsAdvancedOne() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 1 + $multiple, $month[1]];
            }

            // all months advanced 2, 14, 26, ...
            foreach (self::monthsAdvancedTwo() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 2 + $multiple, $month[1]];
            }

            // all months advanced 3, 15, 27, ...
            foreach (self::monthsAdvancedThree() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 3 + $multiple, $month[1]];
            }

            // all months advanced 4, 16, 28, ...
            foreach (self::monthsAdvancedFour() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 4 + $multiple, $month[1]];
            }

            // all months advanced 5, 17, 29, ...
            foreach (self::monthsAdvancedFive() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 5 + $multiple, $month[1]];
            }

            // all months advanced 6, 18, 30, ...
            foreach (self::monthsAdvancedSix() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 6 + $multiple, $month[1]];
            }

            // all months advanced 7, 19, 31, ...
            foreach (self::monthsAdvancedSeven() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 7 + $multiple, $month[1]];
            }

            // all months advanced 8, 20, 32, ...
            foreach (self::monthsAdvancedEight() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 8 + $multiple, $month[1]];
            }

            // all months advanced 9, 21, 33, ...
            foreach (self::monthsAdvancedNine() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 9 + $multiple, $month[1]];
            }

            // all months advanced 10, 22, 34, ...
            foreach (self::monthsAdvancedTen() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 10 + $multiple, $month[1]];
            }

            // all months advanced 11, 23, 35, ...
            foreach (self::monthsAdvancedEleven() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 11 + $multiple, $month[1]];
            }
        }
    }

    /** All months back between 1 and 11 months, 13 and 23 months, 25 and 35 months, ... */
    public static function monthsBack(): iterable
    {
        foreach (self::multiplesOfTwelve() as $multipleKey => $multiple) {
            $multiple = $multiple[0];

            // all months back 1, 13, 25, ...
            foreach (self::monthsBackOne() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 1 + $multiple, $month[1]];
            }

            // all months back 2, 14, 26, ...
            foreach (self::monthsBackTwo() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 2 + $multiple, $month[1]];
            }

            // all months back 3, 15, 27, ...
            foreach (self::monthsBackThree() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 3 + $multiple, $month[1]];
            }

            // all months back 4, 16, 28, ...
            foreach (self::monthsBackFour() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 4 + $multiple, $month[1]];
            }

            // all months back 5, 17, 29, ...
            foreach (self::monthsBackFive() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 5 + $multiple, $month[1]];
            }

            // all months back 6, 18, 30, ...
            foreach (self::monthsBackSix() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 6 + $multiple, $month[1]];
            }

            // all months back 7, 19, 31, ...
            foreach (self::monthsBackSeven() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 7 + $multiple, $month[1]];
            }

            // all months back 8, 20, 32, ...
            foreach (self::monthsBackEight() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 8 + $multiple, $month[1]];
            }

            // all months back 9, 21, 33, ...
            foreach (self::monthsBackNine() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 9 + $multiple, $month[1]];
            }

            // all months back 10, 22, 34, ...
            foreach (self::monthsBackTen() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 10 + $multiple, $month[1]];
            }

            // all months back 11, 23, 35, ...
            foreach (self::monthsBackEleven() as $key => $month) {
                yield "{$key}-plus-{$multipleKey}-months" => [$month[0], 11 + $multiple, $month[1]];
            }
        }
    }

    /** Each month paired with a multiple of twelve to test advance() and back() work with counts greater than a year. */
    public static function monthsAndMultiplesOfTwelve(): iterable
    {
        foreach (self::months() as $monthKey => $month) {
            foreach (self::multiplesOfTwelve() as $multipleKey => $multiple) {
                yield "{$monthKey}-{$multipleKey}" => [$month[0], $multiple[0]];
            }
        }
    }

    /** All months and their distances forward to other months. */
    public static function monthsAndDistancesToOtherMonths(): iterable
    {
        foreach (self::monthsAdvancedOne() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 1];
        }

        foreach (self::monthsAdvancedTwo() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 2];
        }

        foreach (self::monthsAdvancedThree() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 3];
        }

        foreach (self::monthsAdvancedFour() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 4];
        }

        foreach (self::monthsAdvancedFive() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 5];
        }

        foreach (self::monthsAdvancedSix() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 6];
        }

        foreach (self::monthsAdvancedSeven() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 7];
        }

        foreach (self::monthsAdvancedEight() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 8];
        }

        foreach (self::monthsAdvancedNine() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 9];
        }

        foreach (self::monthsAdvancedTen() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 10];
        }

        foreach (self::monthsAdvancedEleven() as $key => $months) {
            yield "distance-to-{$key}" => [...$months, 11];
        }
    }

    /** All months and their distances forward from other months. */
    public static function monthsAndDistancesFromOtherMonths(): iterable
    {
        foreach (self::monthsAndDistancesToOtherMonths() as $key => $arguments) {
            [$from, $to, $distance] = $arguments;
            yield str_replace("-to-", "-from-", $key) => [$to, $from, $distance];
        }
    }

    /** Months and their day counts for a selection of non-leap years. */
    public static function monthsAndDayCountsNonLeapYears(): iterable
    {
        $expectedDayCounts = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $dayCountIndex = 0;

        foreach (Month::cases() as $month) {
            yield "{$month->name}-1800" => [$month, 1800, $expectedDayCounts[$dayCountIndex]];
            yield "{$month->name}-1900" => [$month, 1900, $expectedDayCounts[$dayCountIndex]];
            yield "{$month->name}-2100" => [$month, 2100, $expectedDayCounts[$dayCountIndex]];
            $dayCountIndex = ($dayCountIndex + 1) % 12;
        }

        for ($year = 1901; $year < 2000; $year += 4) {
            foreach (Month::cases() as $month) {
                yield sprintf("%s-%04d", $month->name, $year) => [$month, $year, $expectedDayCounts[$dayCountIndex]];
                yield sprintf("%s-%04d", $month->name, $year + 1) => [$month, $year + 1, $expectedDayCounts[$dayCountIndex]];
                yield sprintf("%s-%04d", $month->name, $year + 2) => [$month, $year + 2, $expectedDayCounts[$dayCountIndex]];
                $dayCountIndex = ($dayCountIndex + 1) % 12;
            }
        }
    }

    /** Months and their day counts for a selection of leap years. */
    public static function monthsAndDayCountsLeapYears(): iterable
    {
        $expectedDayCounts = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $dayCountIndex = 0;

        for ($year = 1904; $year < 2100; $year += 4) {
            foreach (Month::cases() as $month) {
                yield sprintf("%s-%04d", $month->name, $year) => [$month, $year, $expectedDayCounts[$dayCountIndex]];
                $dayCountIndex = ($dayCountIndex + 1) % 12;
            }
        }
    }

    /** Ensure isBefore() returns `true` for all Months before another. */
    #[DataProvider("monthsAndEarlierMonths")]
    public function testIsBefore1(Month $month, Month $earlierMonth): void
    {
        self::assertTrue($earlierMonth->isBefore($month));
    }

    /** Ensure isBefore() returns `false` for the same Month. */
    #[DataProvider("months")]
    public function testIsBefore2(Month $month): void
    {
        self::assertFalse($month->isBefore($month));
    }

    /** Ensure isBefore() returns `false` for all Months after another. */
    #[DataProvider("monthsAndLaterMonths")]
    public function testIsBefore3(Month $month, Month $laterMonth): void
    {
        self::assertFalse($laterMonth->isBefore($month));
    }

    /** Ensure isAfter() returns `true` for all Months after another. */
    #[DataProvider("monthsAndLaterMonths")]
    public function testIsAfter1(Month $month, Month $laterMonth): void
    {
        self::assertTrue($laterMonth->isAfter($month));
    }

    /** Ensure isAfter() returns `false` for the same Month. */
    #[DataProvider("months")]
    public function testIsAfter2(Month $month): void
    {
        self::assertFalse($month->isAfter($month));
    }

    /** Ensure isAfter() returns `false` for all Months before another. */
    #[DataProvider("monthsAndEarlierMonths")]
    public function testIsAfter3(Month $month, Month $earlierMonth): void
    {
        self::assertFalse($earlierMonth->isAfter($month));
    }

    /** Ensure advance() returns the correct Month for day counts that are not multiples of 7. */
    #[DataProvider("monthsAdvanced")]
    public function testAdvance1(Month $month, int $months, Month $expectedDay): void
    {
        self::assertSame($expectedDay, $month->advance($months));
    }

    /** Ensure advance() returns the same month when the month count is a multiple of 12 months. */
    #[DataProvider("monthsAndMultiplesOfTwelve")]
    public function testAdvance2(Month $month, int $months): void
    {
        self::assertSame($month, $month->advance($months));
    }

    /** Ensure back() returns the correct Month for month counts that are not multiples of 12. */
    #[DataProvider("monthsBack")]
    public function testBack1(Month $month, int $months, Month $expectedDay): void
    {
        self::assertSame($expectedDay, $month->back($months));
    }

    /** Ensure back() returns the same Month when the month count is a multiple of 12 months. */
    #[DataProvider("monthsAndMultiplesOfTwelve")]
    public function testBack2(Month $month, int $months): void
    {
        self::assertSame($month, $month->back($months));
    }

    /** Ensure distanceTo() correctly determines the number of months between two Months. */
    #[DataProvider("monthsAndDistancesToOtherMonths")]
    public function testDistanceTo1(Month $from, Month $to, int $expectedDistance): void
    {
        self::assertSame($expectedDistance, $from->distanceTo($to));
    }

    /** Ensure distanceTo() returns 0 for a month and itself. */
    #[DataProvider("months")]
    public function testDistanceTo2(Month $month): void
    {
        self::assertSame(0, $month->distanceTo($month));
    }

    /** Ensure distanceFrom() correctly determines the number of months between two Months. */
    #[DataProvider("monthsAndDistancesFromOtherMonths")]
    public function testDistanceFrom1(Month $to, Month $from, int $expectedDistance): void
    {
        self::assertSame($expectedDistance, $to->distanceFrom($from));
    }

    /** Ensure distanceFrom() returns 0 for a Month and itself. */
    #[DataProvider("months")]
    public function testDistanceFrom2(Month $month): void
    {
        self::assertSame(0, $month->distanceFrom($month));
    }

    /** Ensure the next month is the expected one. */
    #[DataProvider("monthsAdvancedOne")]
    public function testNext1(Month $month, Month $expectedNextMonth): void
    {
        self::assertSame($expectedNextMonth, $month->next());
    }

    /** Ensure the previous month is the expected one. */
    #[DataProvider("monthsBackOne")]
    public function testPrevious1(Month $month, Month $expectedPreviousMonth): void
    {
        self::assertSame($expectedPreviousMonth, $month->previous());
    }

    /** Ensure we get the expected counts for non-leap years. */
    #[DataProvider("monthsAndDayCountsNonLeapYears")]
    public function testDayCount1(Month $month, int $year, int $expectedDayCount): void
    {
        self::assertSame($expectedDayCount, $month->dayCount($year));
    }

    /** Ensure we get the expected counts for leap years. */
    #[DataProvider("monthsAndDayCountsLeapYears")]
    public function testDayCount2(Month $month, int $year, int $expectedDayCount): void
    {
        self::assertSame($expectedDayCount, $month->dayCount($year));
    }
}
