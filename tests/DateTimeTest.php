<?php

namespace MeridiemTests;

use DateTimeInterface as PhpDateTimeInterface;
use DateTime as PhpDateTime;
use DateTimeImmutable as PhpDateTimeImmutable;
use DateTimeZone as PhpDateTimeZone;
use Meridiem\DateTime;
use Meridiem\Month;
use InvalidArgumentException;
use Meridiem\TimeZone;
use Meridiem\UnixEpoch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

// TODO extract XRay from Bead framework and add as dev dependency (see testIsLeapYear...())
// TODO test assertion fires in constructor when assertions are on
// TODO test assertion doesn't fire in constructor when assertions are off
#[CoversClass(DateTime::class)]
class DateTimeTest extends TestCase
{
    public static function invalidYears(): iterable
    {
        yield "too-small" => [-10000];
        yield "too-large" => [10000];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    /** All the years that are valid for a DateTime. */
    public static function validYears(): iterable
    {
        for ($year = -9999; $year <= 9999; ++$year) {
            yield (string) $year => [$year];
        }
    }

    /** A selection of leap years. */
    public static function leapYears(): iterable
    {
        for ($year = 1904; $year < 2100; $year += 4) {
            yield sprintf("leap-%04d", $year) => [$year];
        }

        for ($year = 2104; $year < 2200; $year += 4) {
            yield sprintf("leap-%04d", $year) => [$year];
        }

        for ($year = 2204; $year < 2300; $year += 4) {
            yield sprintf("leap-%04d", $year) => [$year];
        }

        for ($year = 2304; $year <= 2400; $year += 4) {
            yield sprintf("leap-%04d", $year) => [$year];
        }
    }

    /** A selection of non-leap years. */
    public static function nonLeapYears(): iterable
    {
        yield "non-leap-1900" => [1900];
        $skip = 0;

        for ($year = 1904; $year < 2100; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-%04d", $year) => [$year];
        }

        yield "non-leap-2100" => [2100];
        $skip = 0;

        for ($year = 2104; $year < 2200; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-%04d", $year) => [$year];
        }

        yield "non-leap-2200" => [2200];
        $skip = 0;

        for ($year = 2204; $year < 2300; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-%04d", $year) => [$year];
        }

        yield "non-leap-2300" => [2300];
        $skip = 0;

        for ($year = 2304; $year <= 2400; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-%04d", $year) => [$year];
        }
    }

    /** All enumerated months. */
    public static function allMonths(): iterable
    {
        foreach (Month::cases() as $month) {
            yield "month-{$month->name}" => [$month];
        }
    }

    /** All possible valid days. */
    public static function allDays(): iterable
    {
        for ($day = 1; $day <= 31; $day++) {
            yield sprintf("day-%02d", $day) => [$day];
        }
    }

    /** A selection of invalid hours. */
    public static function invalidHours(): iterable
    {
        yield "hour-too-small" => [-1];
        yield "hour-too-large" => [24];
        yield "hour-min-int" => [PHP_INT_MIN];
        yield "hour-max-int" => [PHP_INT_MAX];
    }

    /** All valid hours. */
    public static function validHours(): iterable
    {
        for ($hour = 0; $hour < 24; ++$hour) {
            yield sprintf("hour-%02d", $hour) => [$hour];
        }
    }

    /** A selection of invalid minutes. */
    public static function invalidMinutes(): iterable
    {
        yield "minute-too-small" => [-1];
        yield "minute-too-large" => [60];
        yield "minute-min-int" => [PHP_INT_MIN];
        yield "minute-max-int" => [PHP_INT_MAX];
    }

    /** All valid minutes. */
    public static function validMinutes(): iterable
    {
        for ($minute = 0; $minute < 60; ++$minute) {
            yield sprintf("minute-%02d", $minute) => [$minute];
        }
    }

    /** A selection of invalid seconds. */
    public static function invalidSeconds(): iterable
    {
        yield "second-too-small" => [-1];
        yield "second-too-large" => [60];
        yield "second-min-int" => [PHP_INT_MIN];
        yield "second-max-int" => [PHP_INT_MAX];
    }

    /** All valid seconds. */
    public static function validSeconds(): iterable
    {
        for ($second = 0; $second < 60; ++$second) {
            yield sprintf("second-%02d", $second) => [$second];
        }
    }

    /** A selection of invalid milliseconds. */
    public static function invalidMilliseconds(): iterable
    {
        yield "millisecond-too-small" => [-1];
        yield "millisecond-too-large" => [1000];
        yield "millisecond-min-int" => [PHP_INT_MIN];
        yield "millisecond-max-int" => [PHP_INT_MAX];
    }

    /** All valid milliseconds. */
    public static function validMilliseconds(): iterable
    {
        for ($millisecond = 0; $millisecond < 1000; ++$millisecond) {
            yield sprintf("millisecond-%02d", $millisecond) => [$millisecond];
        }
    }

    /**
     * A selection of valid Gregorian date-time components.
     *
     * Data yielded are:
     * - (int) year
     * - (Month) month
     * - (int) hour
     * - (int) minute
     * - (int) second
     * - (int) millisecond
     * - (TimeZone) timezone
     */
    public static function dataForTestCreate1(): iterable
    {
        $utc = TimeZone::lookup("UTC");
        yield "typical" => [2025, Month::April, 23, 13, 21, 7, 165, $utc];

        foreach (Month::cases() as $month) {
            yield "first-{$month->name}" => [2021, $month, 1, 3, 14, 8, 883, $utc];
            yield "last-{$month->name}" => [2013, $month, $month->dayCount(2025), 23, 52, 43, 901, $utc];
        }

        for ($hour = 0; $hour < 24; ++$hour) {
            yield "start-hour-" . sprintf("%02d", $hour) => [1993, Month::July, 16, $hour, 0, 0, 0, $utc];
            yield "end-hour-" . sprintf("%02d", $hour) => [1997, Month::March, 30, $hour, 59, 59, 999, $utc];
        }

        for ($minute = 0; $minute < 60; ++$minute) {
            yield "start-minute-" . sprintf("%02d", $minute) => [1989, Month::September, 12, 14, $minute, 0, 0, $utc];
            yield "end-minute-" . sprintf("%02d", $minute) => [1981, Month::February, 8, 19, $minute, 59, 999, $utc];
        }

        for ($second = 0; $second < 60; ++$second) {
            yield "start-second-" . sprintf("%02d", $second) => [1951, Month::January, 21, 3, 47, $second, 0, $utc];
            yield "end-second-" . sprintf("%02d", $second) => [1966, Month::November, 10, 12, 33, $second, 999, $utc];
        }

//        yield "timezone-europe-london" => [1975, Month::May, 18, 5, 28, 14, 311, new DateTimeZone("Europe/London")];
        yield "timezone-+0400" => [2001, Month::October, 23, 22, 01, 19, 170, TimeZone::parse("+0400")];
        yield "timezone--0330" => [2001, Month::March, 23, 22, 01, 19, 170, TimeZone::parse("-0330")];
    }

    /** Ensure we can create accurate DateTime instances from date-time components. */
    #[DataProvider("dataForTestCreate1")]
    public function testCreate1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, TimeZone $timeZone): void
    {
        $actual = DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone);
        self::assertSame($year, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
        self::assertSame($hour, $actual->hour());
        self::assertSame($minute, $actual->minute());
        self::assertSame($second, $actual->second());
        self::assertSame($millisecond, $actual->millisecond());
        self::assertSame($timeZone->name(), $actual->timeZone()->name());
    }

    /** Ensure we can create dates in leap years. */
    #[DataProvider("leapYears")]
    public function testCreate2(int $year): void
    {
        $actual = DateTime::create($year, Month::February, 1);
        self::assertSame($year, $actual->year());
        self::assertSame(Month::February, $actual->month());
        self::assertSame(1, $actual->day());
        $actual = DateTime::create($year, Month::February, 29);
        self::assertSame($year, $actual->year());
        self::assertSame(Month::February, $actual->month());
        self::assertSame(29, $actual->day());
        $actual = DateTime::create($year, Month::March, 1);
        self::assertSame($year, $actual->year());
        self::assertSame(Month::March, $actual->month());
        self::assertSame(1, $actual->day());
    }

    /** Ensure non-leap years don't accept 29th Feb. */
    #[DataProvider("nonLeapYears")]
    public function testCreate3(int $year): void
    {
        $actual = DateTime::create($year, Month::February, 1);
        self::assertSame($year, $actual->year());
        self::assertSame(Month::February, $actual->month());
        self::assertSame(1, $actual->day());

        $actual = DateTime::create($year, Month::February, 28);
        self::assertSame($year, $actual->year());
        self::assertSame(Month::February, $actual->month());
        self::assertSame(28, $actual->day());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected day between 1 and 28 inclusive, found 29");
        DateTime::create($year, Month::February, 29);
    }

    public static function dataForTestCreate4(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day <= $month->dayCount(2025); $day++) {
                yield "{$day}-{$month->name}" => [$month, $day];
            }
        }
    }

    /** Ensure all valid days in a non-leap-year can be created successfully. */
    #[DataProvider("dataForTestCreate4")]
    public function testCreate4(Month $month, int $day): void
    {
        $actual = DateTime::create(2025, $month, $day);
        self::assertSame(2025, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
    }

    public static function dataForTestCreate5(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day <= $month->dayCount(2000); $day++) {
                yield "{$day}-{$month->name}" => [$month, $day];
            }
        }
    }

    /** Ensure all valid days in a leap-year can be created successfully. */
    #[DataProvider("dataForTestCreate5")]
    public function testCreate5(Month $month, int $day): void
    {
        $actual = DateTime::create(2000, $month, $day);
        self::assertSame(2000, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
    }

    public static function dataForTestCreate6(): iterable
    {
        foreach (self::validHours() as $hour) {
            foreach (self::validMinutes() as $minute) {
                yield "{$hour[0]}:{$minute[0]}}" => [$hour[0], $minute[0]];
            }
        }
    }

    /** Ensure all valid times of day can be created successfully. */
    #[DataProvider("dataForTestCreate6")]
    public function testCreate6(int $hour, int $minute): void
    {
        $actual = DateTime::create(2025, Month::January, 1, $hour, $minute);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame($hour, $actual->hour());
        self::assertSame($minute, $actual->minute());
    }

    /** Ensure all valid seconds can be created successfully. */
    #[DataProvider("validSeconds")]
    public function testCreate7(int $second): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 0, 0, $second);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(0, $actual->hour());
        self::assertSame(0, $actual->minute());
        self::assertSame($second, $actual->second());

        $actual = DateTime::create(2025, Month::January, 1, 23, 59, $second);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(23, $actual->hour());
        self::assertSame(59, $actual->minute());
        self::assertSame($second, $actual->second());
    }

    /** Ensure all valid milliseconds can be created successfully. */
    #[DataProvider("validMilliseconds")]
    public function testCreate8(int $millisecond): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 0, 0, 0, $millisecond);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(0, $actual->hour());
        self::assertSame(0, $actual->minute());
        self::assertSame(0, $actual->second());
        self::assertSame($millisecond, $actual->millisecond());

        $actual = DateTime::create(2025, Month::January, 1, 23, 59, 59, $millisecond);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(23, $actual->hour());
        self::assertSame(59, $actual->minute());
        self::assertSame(59, $actual->second());
        self::assertSame($millisecond, $actual->millisecond());
    }

    /** Ensure the default time is midnight. */
    public function testCreate9(): void
    {
        $actual = DateTime::create(2025, Month::January, 1);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(0, $actual->hour());
        self::assertSame(0, $actual->minute());
        self::assertSame(0, $actual->second());
        self::assertSame(0, $actual->millisecond());
    }

    /** Ensure the default millisecond is 0 when a time with seconds is given. */
    public function testCreate10(): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 12, 8, 41);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(12, $actual->hour());
        self::assertSame(8, $actual->minute());
        self::assertSame(41, $actual->second());
        self::assertSame(0, $actual->millisecond());
    }

    /** Ensure the default second and millisecond are both 0 when a time with just hours and minutes is given. */
    public function testCreate11(): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 10, 29);
        self::assertSame(2025, $actual->year());
        self::assertSame(Month::January, $actual->month());
        self::assertSame(1, $actual->day());
        self::assertSame(10, $actual->hour());
        self::assertSame(29, $actual->minute());
        self::assertSame(0, $actual->second());
        self::assertSame(0, $actual->millisecond());
    }

    /** Ensure invalid years throw. */
    #[DataProvider("invalidYears")]
    public function testCreate12(int $year): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected year between -9999 and 9999 inclusive, found {$year}");
        DateTime::create($year, Month::April, 23);
    }

    public static function dataForTestCreate13(): iterable
    {
        foreach (Month::cases() as $month) {
            yield "{$month->name}-negative" => [$month, -1];
            yield "{$month->name}-zero" => [$month, -1];
            yield "{$month->name}-too-high" => [$month, $month->dayCount() + 1];
        }
    }

    /** Ensure invalid days throw. */
    #[DataProvider("dataForTestCreate13")]
    public function testCreate13(Month $month, int $day): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected day between 1 and {$month->dayCount(2025)} inclusive, found {$day}");
        DateTime::create(2025, $month, $day);
    }

    /** Ensure invalid hours throw. */
    #[DataProvider("invalidHours")]
    public function testCreate14(int $hour): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected hour between 0 and 23 inclusive, found {$hour}");
        DateTime::create(2025, Month::January, 1, $hour, 17, 44);
    }

    /** Ensure invalid minutes throw. */
    #[DataProvider("invalidMinutes")]
    public function testCreate15(int $minute): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected minute between 0 and 59 inclusive, found {$minute}");
        DateTime::create(2025, Month::January, 1, 12, $minute, 13);
    }

    /** Ensure invalid seconds throw. */
    #[DataProvider("invalidSeconds")]
    public function testCreate16(int $second): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected second between 0 and 59 inclusive, found {$second}");
        DateTime::create(2025, Month::January, 1, 12, 22, $second);
    }

    /** Ensure invalid milliseconds throw. */
    #[DataProvider("invalidMilliseconds")]
    public function testCreate17(int $millisecond): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected millisecond between 0 and 999 inclusive, found {$millisecond}");
        DateTime::create(2025, Month::January, 1, 12, 22, 50, $millisecond);
    }

    /**
     * A wide selection of PHP DateTime objects to test conversion:
     * - the first and last millisecond in every day of both a leap year and a non-leap year
     * - the first and last millisecond of every minute of a single day
     * - the first and last millisecond of every second of a single minute
     * - every millisecond of a single second
     *
     * TODO trim this down, it's trying to allocate too much RAM
     */
    public static function phpDateTimes(): iterable
    {
        foreach (["UTC"/*, "Africa/Kigali"*/] as $timezoneName) {
            $timezone = new PhpDateTimeZone($timezoneName);

            // Every day of a non-leap year and of a leap year
            foreach ([2025, 2000] as $year) {
                foreach (Month::cases() as $month) {
                    for ($day = 1; $day <= $month->dayCount($year); ++$day) {
                        yield "every-day-{$timezoneName}-{$year}-{$month->name}-{$day} 00:00:00.000" => [
                            PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 00:00:00.000", $year, $month->value, $day), $timezone),
                            $year,
                            $month,
                            $day,
                            0,
                            0,
                            0,
                            0,
                            $timezoneName,
                        ];

                        yield "every-day-{$timezoneName}-{$year}-{$month->name}-{$day} 23:59:59.999" => [
                            PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 23:59:59.999", $year, $month->value, $day), $timezone),
                            $year,
                            $month,
                            $day,
                            23,
                            59,
                            59,
                            999,
                            $timezoneName,
                        ];
                    }
                }
            }

            // Every time of day
            for ($hour = 0; $hour <= 23; $hour++) {
                for ($minute = 0; $minute <= 59; $minute++) {
                    yield "every-time-{$timezoneName}-2025-04-23-{$hour}:{$minute}:00.000" => [
                        PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:00.000", $hour, $minute), $timezone),
                        2025,
                        Month::April,
                        23,
                        $hour,
                        $minute,
                        0,
                        0,
                        $timezoneName,
                    ];

                    yield "every-time-{$timezoneName}-2025-04-23-{$hour}:{$minute}:59.999" => [
                        PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:59.999", $hour, $minute), $timezone),
                        2025,
                        Month::April,
                        23,
                        $hour,
                        $minute,
                        59,
                        999,
                        $timezoneName,
                    ];
                }
            }

            // Every second of a minute
            for ($second = 0; $second <= 59; $second++) {
                yield "every-second-{$timezoneName}-2020-02-29-14:01:{$second}.000" => [
                    PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("2020-02-29 14:01:%02d.000", $second), $timezone),
                    2020,
                    Month::February,
                    29,
                    14,
                    1,
                    $second,
                    0,
                    $timezoneName,
                ];

                yield "every-second-{$timezoneName}-1981-11-30-22:40:{$second}.999" => [
                    PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("1981-11-30 22:40:%02d.999", $second), $timezone),
                    1981,
                    Month::November,
                    30,
                    22,
                    40,
                    $second,
                    999,
                    $timezoneName,
                ];
            }

            // Every millisecond of a second
            for ($millisecond = 0; $millisecond <= 999; $millisecond++) {
                yield "every-millisecond-{$timezoneName}-1965-12-01-03:21:18.{$millisecond}" => [
                    PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("1965-12-01 03:21:18.%03d", $millisecond), $timezone),
                    1965,
                    Month::December,
                    1,
                    3,
                    21,
                    18,
                    $millisecond,
                    $timezoneName,
                ];
            }
        }
    }
//
//    /**
//     * A wide selection of PHP DateTimeImmutable objects to test conversion:
//     * - the first and last millisecond in every day of both a leap year and a non-leap year
//     * - the first and last millisecond of every minute of a single day
//     * - the first and last millisecond of every second of a single minute
//     * - every millisecond of a single second
//     *
//     * TODO trim this down, it's trying to allocate too much RAM
//     */
//    public static function phpDateTimeImmutables(): iterable
//    {
//        $timezoneName = "UTC";
////        foreach (["America/New_York", "Europe/London"] as $timezoneName) {
//            $timezone = new PhpDateTimeZone($timezoneName);
//
//            // Every day of a non-leap year and of a leap year
//            foreach ([2025, 2000] as $year) {
//                foreach (Month::cases() as $month) {
//                    for ($day = 1; $day <= $month->dayCount($year); ++$day) {
//                        yield "every-day-{$timezoneName}-{$year}-{$month->name}-{$day} 00:00:00.000" => [
//                            PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 00:00:00.000", $year, $month->value, $day), $timezone),
//                            $year,
//                            $month,
//                            $day,
//                            0,
//                            0,
//                            0,
//                            0,
//                            $timezoneName,
//                        ];
//
//                        yield "every-day-{$timezoneName}-{$year}-{$month->name}-{$day} 23:59:59.999" => [
//                            PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 23:59:59.999", $year, $month->value, $day), $timezone),
//                            $year,
//                            $month,
//                            $day,
//                            23,
//                            59,
//                            59,
//                            999,
//                            $timezoneName,
//                        ];
//                    }
//                }
//            }
//
//            // Every time of day
//            for ($hour = 0; $hour <= 23; $hour++) {
//                for ($minute = 0; $minute <= 59; $minute++) {
//                    yield "every-time-{$timezoneName}-2025-04-23-{$hour}:{$minute}:00.000" => [
//                        PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:00.000", $hour, $minute), $timezone),
//                        2025,
//                        Month::April,
//                        23,
//                        $hour,
//                        $minute,
//                        0,
//                        0,
//                        $timezoneName,
//                    ];
//
//                    yield "every-time-{$timezoneName}-2025-04-23-{$hour}:{$minute}:59.999" => [
//                        PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:59.999", $hour, $minute), $timezone),
//                        2025,
//                        Month::April,
//                        23,
//                        $hour,
//                        $minute,
//                        59,
//                        999,
//                        $timezoneName,
//                    ];
//                }
//            }
//
//            // Every second of a minute
//            for ($second = 0; $second <= 59; $second++) {
//                yield "every-second-{$timezoneName}-2020-02-29-14:01:{$second}.000" => [
//                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2020-02-29 14:01:%02d.000", $second), $timezone),
//                    2020,
//                    Month::February,
//                    29,
//                    14,
//                    1,
//                    $second,
//                    0,
//                    $timezoneName,
//                ];
//
//                yield "every-second-{$timezoneName}-1981-11-30-22:40:{$second}.999" => [
//                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("1981-11-30 22:40:%02d.999", $second), $timezone),
//                    1981,
//                    Month::November,
//                    30,
//                    22,
//                    40,
//                    $second,
//                    999,
//                    $timezoneName,
//                ];
//            }
//
//            // Every millisecond of a second
//            for ($millisecond = 0; $millisecond <= 999; $millisecond++) {
//                yield "every-millisecond-{$timezoneName}-1965-12-01-01:21:18.{$millisecond}" => [
//                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("1965-12-01 01:21:18.%03d", $millisecond), $timezone),
//                    1965,
//                    Month::December,
//                    1,
//                    1,
//                    21,
//                    18,
//                    $millisecond,
//                    $timezoneName,
//                ];
//            }
////        }
//    }

    /** Ensure PHP DateTime objects can be correctly converted. */
    #[DataProvider("phpDateTimes")]
    public function testFromDateTime1(PhpDateTimeInterface $dateTime, int $expectedYear, Month $expectedMonth, int $expectedDay, int $expectedHour, int $expectedMinute, int $expectedSecond, int $expectedMillisecond, string $expectedTimeZone): void
    {
        $actual = DateTime::fromDateTime($dateTime);
        self::assertSame($expectedYear, $actual->year());
        self::assertSame($expectedMonth, $actual->month());
        self::assertSame($expectedDay, $actual->day());
        self::assertSame($expectedHour, $actual->hour());
        self::assertSame($expectedMinute, $actual->minute());
        self::assertSame($expectedSecond, $actual->second());
        self::assertSame($expectedMillisecond, $actual->millisecond());
        self::assertSame($expectedTimeZone, $actual->timeZone()->name());
    }

//    /** Ensure PHP DateTimeImmutable objects can be correctly converted. */
//    #[DataProvider("phpDateTimeImmutables")]
//    public function testFromDateTime2(PhpDateTimeInterface $dateTime, int $expectedYear, Month $expectedMonth, int $expectedDay, int $expectedHour, int $expectedMinute, int $expectedSecond, int $expectedMillisecond, string $expectedTimeZone): void
//    {
//        $actual = DateTime::fromDateTime($dateTime);
//        self::assertSame($expectedYear, $actual->year());
//        self::assertSame($expectedMonth, $actual->month());
//        self::assertSame($expectedDay, $actual->day());
//        self::assertSame($expectedHour, $actual->hour());
//        self::assertSame($expectedMinute, $actual->minute());
//        self::assertSame($expectedSecond, $actual->second());
//        self::assertSame($expectedMillisecond, $actual->millisecond());
//        self::assertSame($expectedTimeZone, $actual->timeZone()->name());
//    }

    public static function dataForTestFromDateTime3(): iterable
    {
        yield "epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second, UnixEpoch::Millisecond, 0, 0];
        yield "millisecond-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second, UnixEpoch::Millisecond + 1, 0, 1];
        yield "millisecond-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), 31, 23, 59, 59, 999, -1, -1];
        yield "second-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second + 1, 0, 1, 1000];
        yield "second-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), 31, 23, 59, 59, 0, -1, -1000];
        yield "minute-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute + 1, 0, 0, 60, 60000];
        yield "minute-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), 31, 23, 59, 0, 0, -60, -60000];
        yield "hour-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour + 1, 0, 0, 0, 3600, 3600000];
        yield "hour-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), 31, 23, 0, 0, 0, -3600, -3600000];
        yield "day-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month, UnixEpoch::Day + 1, 0, 0, 0, 0, 86400, 86400000];
        yield "day-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), 31, 0, 0, 0, 0, -86400, -86400000];
        $days = Month::January->dayCount(UnixEpoch::Year);
        yield "month-after-epoch" => [UnixEpoch::Year, UnixEpoch::Month->next(), UnixEpoch::Day, 0, 0, 0, 0, $days * 86400, $days * 86400000];
        $days = UnixEpoch::Month->previous()->dayCount(UnixEpoch::Year - 1);
        yield "month-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month->previous(), UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second, UnixEpoch::Millisecond, $days * -86400, $days * -86400000];
        yield "year-after-epoch" => [UnixEpoch::Year + 1, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second, UnixEpoch::Millisecond, 365 * 86400, 365 * 86400000];
        yield "year-before-epoch" => [UnixEpoch::Year - 1, UnixEpoch::Month, UnixEpoch::Day, UnixEpoch::Hour, UnixEpoch::Minute, UnixEpoch::Second, UnixEpoch::Millisecond, 365 * -86400, 365 * -86400000];
        yield "leap-year-after-epoch" => [1972, Month::March, 1, 0, 0, 0, 0, 68256000, 68256000000];
        yield "leap-year-before-epoch" => [1968, Month::February, 29, 23, 59, 59, 999, -57974401, -57974400001];
        yield "long-after-epoch" => [2025, Month::February, 19, 22, 18, 13, 511, 1740003493, 1740003493511];
        yield "long-before-epoch" => [1931, Month::November, 14, 8, 22, 40, 388, -1203349040, -1203349039612];
    }

    /** Ensure Unix timestamps are calculated accurately from Gregorian initialisation. */
    #[DataProvider("dataForTestFromDateTime3")]
    public function testFromDateTime3(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, int $expectedUnix, int $expectedUnixMs): void
    {
        $dateTime = DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond);
        self::assertSame($expectedUnix, $dateTime->unixTimestamp());
        self::assertSame($expectedUnixMs, $dateTime->unixTimestampMs());
    }

    public static function unixTimestamps(): iterable
    {
        yield "unix-epoch" => [0, 1970, Month::January, 1, 0, 0, 0];
        yield "unix-one-second" => [1, 1970, Month::January, 1, 0, 0, 1];
        yield "unix-leap-year" => [951826154, 2000, Month::February, 29, 12, 9, 14];
    }

    /** Ensure we can correctly instantiate from unix timestamps. */
    #[DataProvider('unixTimestamps')]
    public function testFromUnixTimestamp1(int $timestamp, int $expectedYear, Month $expectedMonth, int $expectedDay, int $expectedHour, int $expectedMinute, int $expectedSecond): void
    {
        $actual = DateTime::fromUnixTimestamp($timestamp);
        self::assertSame($expectedYear, $actual->year());
        self::assertSame($expectedMonth, $actual->month());
        self::assertSame($expectedDay, $actual->day());
        self::assertSame($expectedHour, $actual->hour());
        self::assertSame($expectedMinute, $actual->minute());
        self::assertSame($expectedSecond, $actual->second());
        self::assertSame($timestamp, $actual->unixTimestamp());
        self::assertSame($timestamp * 1000, $actual->unixTimestampMs());
    }

    /** Ensure we can accurately detect leap years. */
    #[DataProvider("leapYears")]
    public function testIsLeapYear1(int $year): void
    {
        $method = new ReflectionMethod(DateTime::class, "isLeapYear");
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null, $year));
    }

    /** Ensure we can accurately detect non-leap years. */
    #[DataProvider("nonLeapYears")]
    public function testIsLeapYear2(int $year): void
    {
        $method = new ReflectionMethod(DateTime::class, "isLeapYear");
        $method->setAccessible(true);
        self::assertFalse($method->invoke(null, $year));
    }

    /** Ensure we accurately retrieve the year. */
    #[DataProvider("validYears")]
    public function testYear1(int $year): void
    {
        $actual = DateTime::create($year, Month::January, 1);
        self::assertSame($year, $actual->year());
    }

    /** Ensure we accurately retrieve the year. */
    #[DataProvider("allMonths")]
    public function testMonth1(Month $month): void
    {
        $actual = DateTime::create(2025, $month, 1);
        self::assertSame($month, $actual->month());
    }

    /** Ensure we accurately retrieve the day. */
    #[DataProvider("allDays")]
    public function testDay1(int $day): void
    {
        $actual = DateTime::create(2025, Month::January, $day);
        self::assertSame($day, $actual->day());
    }

    /** Ensure we accurately retrieve the day. */
    #[DataProvider("validHours")]
    public function testHour1(int $hour): void
    {
        $actual = DateTime::create(2025, Month::January, 1, $hour, 0);
        self::assertSame($hour, $actual->hour());
    }

    /** Ensure we accurately retrieve the day. */
    #[DataProvider("validMinutes")]
    public function testMinute1(int $minute): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 0, $minute);
        self::assertSame($minute, $actual->minute());
    }

    /** Ensure we accurately retrieve the day. */
    #[DataProvider("validSeconds")]
    public function testSecond1(int $second): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 0, 0, $second);
        self::assertSame($second, $actual->second());
    }

    /** Ensure we accurately retrieve the day. */
    #[DataProvider("validMilliseconds")]
    public function testMillisecond1(int $millisecond): void
    {
        $actual = DateTime::create(2025, Month::January, 1, 0, 0, 0, $millisecond);
        self::assertSame($millisecond, $actual->millisecond());
    }

    // TODO test now()
    // TODO test withDate()
    // TODO test weekday()
    // TODO test withTime()
    // TODO test withHour()
    // TODO test withMinute()
    // TODO test withSecond()
    // TODO test withMillisecond()
    // TODO test withTimeZone()
    // TODO test timeZone()
    // TODO test unixTimestamp()
    // TODO test unixTimestampMs()
    // TODO test isBefore()
    // TODO test isAfter()
    // TODO test isEqualTo()
    // TODO test isInSameYearAs()
    // TODO test isInSameMonthAs()
    // TODO test isOnSameDayAs()
    // TODO test isInSameHourAs()
    // TODO test isInSameMinuteAs()
    // TODO test isInSameSecondAs()
}
