<?php

namespace MeridiemTests;

use Meridiem\Weekday;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WeekdayTest extends TestCase
{
    /** All weekdays. */
    public static function days(): iterable
    {
        foreach (Weekday::cases() as $day) {
            yield $day->name => [$day];
        }
    }

    /** Each day that has days before it paired with each of the days before it. */
    public static function daysAndEarlierDays(): iterable
    {
        yield 'tuesday-monday' => [Weekday::Tuesday, Weekday::Monday];

        yield 'wednesday-monday' => [Weekday::Wednesday, Weekday::Monday];
        yield 'wednesday-tuesday' => [Weekday::Wednesday, Weekday::Tuesday];

        yield 'thursday-monday' => [Weekday::Thursday, Weekday::Monday];
        yield 'thursday-tuesday' => [Weekday::Thursday, Weekday::Tuesday];
        yield 'thursday-wednesday' => [Weekday::Thursday, Weekday::Wednesday];

        yield 'friday-monday' => [Weekday::Friday, Weekday::Monday];
        yield 'friday-tuesday' => [Weekday::Friday, Weekday::Tuesday];
        yield 'friday-wednesday' => [Weekday::Friday, Weekday::Wednesday];
        yield 'friday-thursday' => [Weekday::Friday, Weekday::Thursday];

        yield 'saturday-monday' => [Weekday::Saturday, Weekday::Monday];
        yield 'saturday-tuesday' => [Weekday::Saturday, Weekday::Tuesday];
        yield 'saturday-wednesday' => [Weekday::Saturday, Weekday::Wednesday];
        yield 'saturday-thursday' => [Weekday::Saturday, Weekday::Thursday];
        yield 'saturday-friday' => [Weekday::Saturday, Weekday::Friday];

        yield 'sunday-monday' => [Weekday::Sunday, Weekday::Monday];
        yield 'sunday-tuesday' => [Weekday::Sunday, Weekday::Tuesday];
        yield 'sunday-wednesday' => [Weekday::Sunday, Weekday::Wednesday];
        yield 'sunday-thursday' => [Weekday::Sunday, Weekday::Thursday];
        yield 'sunday-friday' => [Weekday::Sunday, Weekday::Friday];
        yield 'sunday-saturday' => [Weekday::Sunday, Weekday::Saturday];
    }

    /** Each day that has days after it paired with each of the days after it. */
    public static function daysAndLaterDays(): iterable
    {
        yield 'monday-tuesday' => [Weekday::Monday, Weekday::Tuesday];
        yield 'monday-wednesday' => [Weekday::Monday, Weekday::Wednesday];
        yield 'monday-thursday' => [Weekday::Monday, Weekday::Thursday];
        yield 'monday-friday' => [Weekday::Monday, Weekday::Friday];
        yield 'monday-saturday' => [Weekday::Monday, Weekday::Saturday];
        yield 'monday-sunday' => [Weekday::Monday, Weekday::Sunday];

        yield 'tuesday-wednesday' => [Weekday::Tuesday, Weekday::Wednesday];
        yield 'tuesday-thursday' => [Weekday::Tuesday, Weekday::Thursday];
        yield 'tuesday-friday' => [Weekday::Tuesday, Weekday::Friday];
        yield 'tuesday-saturday' => [Weekday::Tuesday, Weekday::Saturday];
        yield 'tuesday-sunday' => [Weekday::Tuesday, Weekday::Sunday];

        yield 'wednesday-thursday' => [Weekday::Wednesday, Weekday::Thursday];
        yield 'wednesday-friday' => [Weekday::Wednesday, Weekday::Friday];
        yield 'wednesday-saturday' => [Weekday::Wednesday, Weekday::Saturday];
        yield 'wednesday-sunday' => [Weekday::Wednesday, Weekday::Sunday];

        yield 'thursday-friday' => [Weekday::Thursday, Weekday::Friday];
        yield 'thursday-saturday' => [Weekday::Thursday, Weekday::Saturday];
        yield 'thursday-sunday' => [Weekday::Thursday, Weekday::Sunday];

        yield 'friday-saturday' => [Weekday::Friday, Weekday::Saturday];
        yield 'friday-sunday' => [Weekday::Friday, Weekday::Sunday];

        yield 'saturday-sunday' => [Weekday::Saturday, Weekday::Sunday];
    }

    public static function multiplesOfSeven(): iterable
    {
        for ($factor = 0; $factor < 5; ++$factor) {
            $multiple = 7 * $factor;
            yield (string) $multiple => [$multiple];
        }
    }

    /** Each Weekday paired with the Weekday 1 day after it. */
    public static function daysAdvancedOne(): iterable
    {
        yield "monday-advanced-one" => [Weekday::Monday, Weekday::Tuesday];
        yield "tuesday-advanced-one" => [Weekday::Tuesday, Weekday::Wednesday];
        yield "wednesday-advanced-one" => [Weekday::Wednesday, Weekday::Thursday];
        yield "thursday-advanced-one" => [Weekday::Thursday, Weekday::Friday];
        yield "friday-advanced-one" => [Weekday::Friday, Weekday::Saturday];
        yield "saturday-advanced-one" => [Weekday::Saturday, Weekday::Sunday];
        yield "sunday-advanced-one" => [Weekday::Sunday, Weekday::Monday];
    }

    /** Each Weekday paired with the Weekday 2 days after it. */
    public static function daysAdvancedTwo(): iterable
    {
        yield "monday-advanced-two" => [Weekday::Monday, Weekday::Wednesday];
        yield "tuesday-advanced-two" => [Weekday::Tuesday, Weekday::Thursday];
        yield "wednesday-advanced-two" => [Weekday::Wednesday, Weekday::Friday];
        yield "thursday-advanced-two" => [Weekday::Thursday, Weekday::Saturday];
        yield "friday-advanced-two" => [Weekday::Friday, Weekday::Sunday];
        yield "saturday-advanced-two" => [Weekday::Saturday, Weekday::Monday];
        yield "sunday-advanced-two" => [Weekday::Sunday, Weekday::Tuesday];
    }

    /** Each Weekday paired with the Weekday 3 days after it. */
    public static function daysAdvancedThree(): iterable
    {
        yield "monday-advanced-three" => [Weekday::Monday, Weekday::Thursday];
        yield "tuesday-advanced-three" => [Weekday::Tuesday, Weekday::Friday];
        yield "wednesday-advanced-three" => [Weekday::Wednesday, Weekday::Saturday];
        yield "thursday-advanced-three" => [Weekday::Thursday, Weekday::Sunday];
        yield "friday-advanced-three" => [Weekday::Friday, Weekday::Monday];
        yield "saturday-advanced-three" => [Weekday::Saturday, Weekday::Tuesday];
        yield "sunday-advanced-three" => [Weekday::Sunday, Weekday::Wednesday];
    }

    /** Each Weekday paired with the Weekday 4 days after it. */
    public static function daysAdvancedFour(): iterable
    {
        yield "monday-advanced-four" => [Weekday::Monday, Weekday::Friday];
        yield "tuesday-advanced-four" => [Weekday::Tuesday, Weekday::Saturday];
        yield "wednesday-advanced-four" => [Weekday::Wednesday, Weekday::Sunday];
        yield "thursday-advanced-four" => [Weekday::Thursday, Weekday::Monday];
        yield "friday-advanced-four" => [Weekday::Friday, Weekday::Tuesday];
        yield "saturday-advanced-four" => [Weekday::Saturday, Weekday::Wednesday];
        yield "sunday-advanced-four" => [Weekday::Sunday, Weekday::Thursday];
    }

    /** Each Weekday paired with the Weekday 5 days after it. */
    public static function daysAdvancedFive(): iterable
    {
        yield "monday-advanced-five" => [Weekday::Monday, Weekday::Saturday];
        yield "tuesday-advanced-five" => [Weekday::Tuesday, Weekday::Sunday];
        yield "wednesday-advanced-five" => [Weekday::Wednesday, Weekday::Monday];
        yield "thursday-advanced-five" => [Weekday::Thursday, Weekday::Tuesday];
        yield "friday-advanced-five" => [Weekday::Friday, Weekday::Wednesday];
        yield "saturday-advanced-five" => [Weekday::Saturday, Weekday::Thursday];
        yield "sunday-advanced-five" => [Weekday::Sunday, Weekday::Friday];
    }

    /** Each Weekday paired with the Weekday 6 days after it. */
    public static function daysAdvancedSix(): iterable
    {
        yield "monday-advanced-six" => [Weekday::Monday, Weekday::Sunday];
        yield "tuesday-advanced-six" => [Weekday::Tuesday, Weekday::Monday];
        yield "wednesday-advanced-six" => [Weekday::Wednesday, Weekday::Tuesday];
        yield "thursday-advanced-six" => [Weekday::Thursday, Weekday::Wednesday];
        yield "friday-advanced-six" => [Weekday::Friday, Weekday::Thursday];
        yield "saturday-advanced-six" => [Weekday::Saturday, Weekday::Friday];
        yield "sunday-advanced-six" => [Weekday::Sunday, Weekday::Saturday];
    }

    /** Each Weekday paired with the Weekday 1 day before it. */
    public static function daysBackOne(): iterable
    {
        foreach (self::daysAdvancedSix() as $key => $days) {
            yield str_replace("advanced-six", "back-one", $key) => $days;
        }
    }

    /** Each Weekday paired with the Weekday 2 days before it. */
    public static function daysBackTwo(): iterable
    {
        foreach (self::daysAdvancedFive() as $key => $days) {
            yield str_replace("advanced-five", "back-two", $key) => $days;
        }
    }

    /** Each Weekday paired with the Weekday 3 days before it. */
    public static function daysBackThree(): iterable
    {
        foreach (self::daysAdvancedFour() as $key => $days) {
            yield str_replace("advanced-four", "back-three", $key) => $days;
        }
    }

    /** Each Weekday paired with the Weekday 4 days before it. */
    public static function daysBackFour(): iterable
    {
        foreach (self::daysAdvancedThree() as $key => $days) {
            yield str_replace("advanced-three", "back-four", $key) => $days;
        }
    }

    /** Each Weekday paired with the Weekday 5 days before it. */
    public static function daysBackFive(): iterable
    {
        foreach (self::daysAdvancedTwo() as $key => $days) {
            yield str_replace("advanced-two", "back-five", $key) => $days;
        }
    }

    /** Each Weekday paired with the Weekday 6 days before it. */
    public static function daysBackSix(): iterable
    {
        foreach (self::daysAdvancedOne() as $key => $days) {
            yield str_replace("advanced-one", "back-six", $key) => $days;
        }
    }

    /** All days advanced by between 1 and 6 days, 8 and 13 days, 15 and 20 days, ... */
    public static function daysAdvanced(): iterable
    {
        foreach (self::multiplesOfSeven() as $multipleKey => $multiple) {
            $multiple = $multiple[0];

            // all days advanced 1, 8, 15, ...
            foreach (self::daysAdvancedOne() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 1 + $multiple, $day[1]];
            }

            // all days advanced 2, 9, 16, ...
            foreach (self::daysAdvancedTwo() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 2 + $multiple, $day[1]];
            }

            // all days advanced 3, 10, 17, ...
            foreach (self::daysAdvancedThree() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 3 + $multiple, $day[1]];
            }

            // all days advanced 4, 11, 18, ...
            foreach (self::daysAdvancedFour() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 4 + $multiple, $day[1]];
            }

            // all days advanced 5, 12, 19, ...
            foreach (self::daysAdvancedFive() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 5 + $multiple, $day[1]];
            }

            // all days advanced 6, 13, 20, ...
            foreach (self::daysAdvancedSix() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 6 + $multiple, $day[1]];
            }
        }
    }

    /** All days back between 1 and 6 days, 8 and 13 days, 15 and 20 days, ... */
    public static function daysBack(): iterable
    {
        foreach (self::multiplesOfSeven() as $multipleKey => $multiple) {
            $multiple = $multiple[0];

            // all days advanced 1, 8, 15, ...
            foreach (self::daysBackOne() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 1 + $multiple, $day[1]];
            }

            // all days advanced 2, 9, 16, ...
            foreach (self::daysBackTwo() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 2 + $multiple, $day[1]];
            }

            // all days advanced 3, 10, 17, ...
            foreach (self::daysBackThree() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 3 + $multiple, $day[1]];
            }

            // all days advanced 4, 11, 18, ...
            foreach (self::daysBackFour() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 4 + $multiple, $day[1]];
            }

            // all days advanced 5, 12, 19, ...
            foreach (self::daysBackFive() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 5 + $multiple, $day[1]];
            }

            // all days advanced 6, 13, 20, ...
            foreach (self::daysBackSix() as $key => $day) {
                yield "{$key}-plus-{$multipleKey}-days" => [$day[0], 6 + $multiple, $day[1]];
            }
        }
    }

    /** All days paired with a multiples of seven to test advance() and back() work with counts greater than a week. */
    public static function daysAndMultiplesOfSeven(): iterable
    {
        foreach (self::days() as $dayKey => $day) {
            foreach (self::multiplesOfSeven() as $multipleKey => $multiple) {
                yield "{$dayKey}-{$multipleKey}" => [$day[0], $multiple[0]];
            }
        }
    }

    /** Ensure isBefore() returns `true` for all Weekdays before another. */
    #[DataProvider("daysAndEarlierDays")]
    public function testIsBefore1(Weekday $day, Weekday $earlierDay): void
    {
        self::assertTrue($earlierDay->isBefore($day));
    }

    /** Ensure isBefore() returns `false` for the same Weekday. */
    #[DataProvider("days")]
    public function testIsBefore2(Weekday $day): void
    {
        self::assertFalse($day->isBefore($day));
    }

    /** Ensure isBefore() returns `false` for all Weekdays after another. */
    #[DataProvider("daysAndLaterDays")]
    public function testIsBefore3(Weekday $day, Weekday $laterDay): void
    {
        self::assertFalse($laterDay->isBefore($day));
    }

    /** Ensure isAfter() returns `true` for all Weekdays after another. */
    #[DataProvider("daysAndLaterDays")]
    public function testIsAfter1(Weekday $day, Weekday $laterDay): void
    {
        self::assertTrue($laterDay->isAfter($day));
    }

    /** Ensure isAfter() returns `false` for the same Weekday. */
    #[DataProvider("days")]
    public function testIsAfter2(Weekday $day): void
    {
        self::assertFalse($day->isAfter($day));
    }

    /** Ensure isAfter() returns `false` for all Weekdays before another. */
    #[DataProvider("daysAndEarlierDays")]
    public function testIsAfter3(Weekday $day, Weekday $earlierDay): void
    {
        self::assertFalse($earlierDay->isAfter($day));
    }

    /** Ensure advance() returns the correct Weekday for day counts that are not multiples of 7. */
    #[DataProvider("daysAdvanced")]
    public function testAdvance1(Weekday $day, int $days, Weekday $expectedDay): void
    {
        self::assertSame($expectedDay, $day->advance($days));
    }

    /** Ensure advance() returns the same day when the day count is a multiple of 7 days. */
    #[DataProvider("daysAndMultiplesOfSeven")]
    public function testAdvance2(Weekday $day, int $days): void
    {
        self::assertSame($day, $day->advance($days));
    }

    /** Ensure back() returns the correct Weekday for day counts that are not multiples of 7. */
    #[DataProvider("daysBack")]
    public function testBack1(Weekday $day, int $days, Weekday $expectedDay): void
    {
        self::assertSame($expectedDay, $day->back($days));
    }

    /** Ensure back() returns the same day when the day count is a multiple of 7 days. */
    #[DataProvider("daysAndMultiplesOfSeven")]
    public function testBack2(Weekday $day, int $days): void
    {
        self::assertSame($day, $day->back($days));
    }

    /** Ensure distanceTo() correctly determines the number of days between two Weekdays. */
    // TODO #[DataProvider()]
    public function testDistanceTo1(Weekday $from, Weekday $to, int $expectedDistance): void
    {
        self::assertSame($expectedDistance, $from->distanceTo($to));
    }

    /** Ensure distanceTo() returns 0 for a day and itself. */
    #[DataProvider("days")]
    public function testDistanceTo2(Weekday $day): void
    {
        self::assertSame(0, $day->distanceTo($day));
    }

    /** Ensure distanceTo() correctly determines the number of days between two Weekdays. */
    // TODO #[DataProvider()]
    public function testDistanceFrom1(Weekday $to, Weekday $from, int $expectedDistance): void
    {
        self::assertSame($expectedDistance, $to->distanceFrom($from));
    }

    /** Ensure distanceFrom() returns 0 for a day and itself. */
    #[DataProvider("days")]
    public function testDistanceFrom2(Weekday $day): void
    {
        self::assertSame(0, $day->distanceFrom($day));
    }
}
