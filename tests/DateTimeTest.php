<?php

namespace MeridiemTests;

use DateTimeInterface as PhpDateTimeInterface;
use DateTime as PhpDateTime;
use DateTimeImmutable as PhpDateTimeImmutable;
use DateTimeZone;
use Meridiem\DateTime;
use Meridiem\Month;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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

    public static function validYears(): iterable
    {
        for ($year = -9999; $year <= 9999; ++$year) {
            yield (string) $year => [$year];
        }
    }

    public static function leapYears(): iterable
    {
        for ($year = 1904; $year < 2100; $year += 4) {
            yield (string) $year => [$year];
        }

        for ($year = 2104; $year < 2200; $year += 4) {
            yield (string) $year => [$year];
        }

        for ($year = 2204; $year < 2300; $year += 4) {
            yield (string) $year => [$year];
        }

        for ($year = 2304; $year <= 2400; $year += 4) {
            yield (string) $year => [$year];
        }
    }

    public static function nonLeapYears(): iterable
    {
        yield "1900" => [1900];
        $skip = 0;

        for ($year = 1904; $year < 2100; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield (string) $year => [$year];
        }

        yield "2100" => [2100];
        $skip = 0;

        for ($year = 2104; $year < 2200; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield (string) $year => [$year];
        }

        yield "2200" => [2200];
        $skip = 0;

        for ($year = 2204; $year < 2300; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield (string) $year => [$year];
        }

        yield "2300" => [2300];
        $skip = 0;

        for ($year = 2304; $year <= 2400; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield (string) $year => [$year];
        }
    }

    public static function invalidHours(): iterable
    {
        yield "too-small" => [-1];
        yield "too-large" => [24];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    public static function validHours(): iterable
    {
        for ($hour = 0; $hour < 24; ++$hour) {
            yield (string) $hour => [$hour];
        }
    }

    public static function invalidMinutes(): iterable
    {
        yield "too-small" => [-1];
        yield "too-large" => [60];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    public static function validMinutes(): iterable
    {
        for ($minute = 0; $minute < 60; ++$minute) {
            yield (string) $minute => [$minute];
        }
    }

    public static function invalidSeconds(): iterable
    {
        yield "too-small" => [-1];
        yield "too-large" => [60];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    public static function validSeconds(): iterable
    {
        for ($second = 0; $second < 60; ++$second) {
            yield (string) $second => [$second];
        }
    }

    public static function invalidMilliseconds(): iterable
    {
        yield "too-small" => [-1];
        yield "too-large" => [1000];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    public static function validMilliseconds(): iterable
    {
        for ($millisecond = 0; $millisecond < 1000; ++$millisecond) {
            yield (string) $millisecond => [$millisecond];
        }
    }

    public static function dataForTestCreate1(): iterable
    {
        $utc = new DateTimeZone('UTC');
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

        yield "timezone-europe-london" => [1975, Month::May, 18, 5, 28, 14, 311, new DateTimeZone("Europe/London")];
        yield "timezone-+0400" => [2001, Month::October, 23, 22, 01, 19, 170, new DateTimeZone("+0400")];
        yield "timezone--0330" => [2001, Month::March, 23, 22, 01, 19, 170, new DateTimeZone("-0330")];
    }

    /** Ensure we can create accurate DateTime instances from date-time components. */
    #[DataProvider("dataForTestCreate1")]
    public function testCreate1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, DateTimeZone $timeZone): void
    {
        $actual = DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone);
        self::assertSame($year, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
        self::assertSame($hour, $actual->hour());
        self::assertSame($minute, $actual->minute());
        self::assertSame($second, $actual->second());
        self::assertSame($millisecond, $actual->millisecond());
        self::assertSame($timeZone->getName(), $actual->timeZone()->getName());
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

        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected day between 1 and {$month->dayCount(2025)} inclusive, found {$day}");
        DateTime::create(2025, $month, $day);
    }

    /** Ensure invalid hours throw. */
    #[DataProvider("invalidHours")]
    public function testCreate14(int $hour): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected hour between 0 and 23 inclusive, found {$hour}");
        DateTime::create(2025, Month::January, 1, $hour, 17, 44);
    }

    /** Ensure invalid minutes throw. */
    #[DataProvider("invalidMinutes")]
    public function testCreate15(int $minute): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected minute between 0 and 59 inclusive, found {$minute}");
        DateTime::create(2025, Month::January, 1, 12, $minute, 13);
    }

    /** Ensure invalid seconds throw. */
    #[DataProvider("invalidSeconds")]
    public function testCreate16(int $second): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected second between 0 and 59 inclusive, found {$second}");
        DateTime::create(2025, Month::January, 1, 12, 22, $second);
    }

    /** Ensure invalid milliseconds throw. */
    #[DataProvider("invalidMilliseconds")]
    public function testCreate17(int $millisecond): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected millisecond between 0 and 999 inclusive, found {$millisecond}");
        DateTime::create(2025, Month::January, 1, 12, 22, 50, $millisecond);
    }

    public static function phpDateTimes(): iterable
    {
        foreach (["UTC", "Africa/Kigali"] as $timezoneName) {
            $timezone = new DateTimeZone($timezoneName);

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
                    PhpDateTime::createFromFormat("Y-m-d H:i:s.v", sprintf("1965-12-01 01:21:18.%03d", $millisecond), $timezone),
                    1965,
                    Month::December,
                    1,
                    1,
                    21,
                    18,
                    $millisecond,
                    $timezoneName,
                ];
            }
        }
    }


    public static function phpDateTimeImmutables(): iterable
    {
        foreach (["America/New_York", "Africa/Kigali"] as $timezoneName) {
            $timezone = new DateTimeZone($timezoneName);

            // Every day of a non-leap year and of a leap year
            foreach ([2025, 2000] as $year) {
                foreach (Month::cases() as $month) {
                    for ($day = 1; $day <= $month->dayCount($year); ++$day) {
                        yield "every-day-{$timezoneName}-{$year}-{$month->name}-{$day} 00:00:00.000" => [
                            PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 00:00:00.000", $year, $month->value, $day), $timezone),
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
                            PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("%04d-%02d-%02d 23:59:59.999", $year, $month->value, $day), $timezone),
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
                        PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:00.000", $hour, $minute), $timezone),
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
                        PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2025-04-23 %02d:%02d:59.999", $hour, $minute), $timezone),
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
                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("2020-02-29 14:01:%02d.000", $second), $timezone),
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
                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("1981-11-30 22:40:%02d.999", $second), $timezone),
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
                    PhpDateTimeImmutable::createFromFormat("Y-m-d H:i:s.v", sprintf("1965-12-01 01:21:18.%03d", $millisecond), $timezone),
                    1965,
                    Month::December,
                    1,
                    1,
                    21,
                    18,
                    $millisecond,
                    $timezoneName,
                ];
            }
        }
    }

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
        self::assertSame($expectedTimeZone, $actual->timeZone()->getName());
    }

    /** Ensure PHP DateTimeImmutable objects can be correctly converted. */
    #[DataProvider("phpDateTimeImmutables")]
    public function testFromDateTime2(PhpDateTimeInterface $dateTime, int $expectedYear, Month $expectedMonth, int $expectedDay, int $expectedHour, int $expectedMinute, int $expectedSecond, int $expectedMillisecond, string $expectedTimeZone): void
    {
        $actual = DateTime::fromDateTime($dateTime);
        self::assertSame($expectedYear, $actual->year());
        self::assertSame($expectedMonth, $actual->month());
        self::assertSame($expectedDay, $actual->day());
        self::assertSame($expectedHour, $actual->hour());
        self::assertSame($expectedMinute, $actual->minute());
        self::assertSame($expectedSecond, $actual->second());
        self::assertSame($expectedMillisecond, $actual->millisecond());
        self::assertSame($expectedTimeZone, $actual->timeZone()->getName());
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
}
