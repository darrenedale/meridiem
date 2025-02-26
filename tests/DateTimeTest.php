<?php

namespace MeridiemTests;

use DateTimeInterface as PhpDateTimeInterface;
use DateTime as PhpDateTime;
use DateTimeImmutable as PhpDateTimeImmutable;
use DateTimeZone as PhpDateTimeZone;
use Equit\XRay\StaticXRay;
use Equit\XRay\XRay;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\DateTime;
use Meridiem\Month;
use InvalidArgumentException;
use Meridiem\PhpDateTimeFormatter;
use Meridiem\TimeZone;
use Meridiem\UnixEpoch;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateTime::class)]
class DateTimeTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    /** Helper to generate a mock implementation of the DateTime contract. */
    private static function mockDateTime(int $year = UnixEpoch::Year, Month $month = UnixEpoch::Month, int $day = UnixEpoch::Day, int $hour = UnixEpoch::Hour, int $minute = UnixEpoch::Minute, int $second = UnixEpoch::Second, int $millisecond = UnixEpoch::Millisecond, int $timestamp = 0): DateTimeContract
    {
        $dateTime = Mockery::mock(DateTimeContract::class);
        $dateTime->shouldReceive('year')->zeroOrMoreTimes()->andReturn($year)->byDefault();
        $dateTime->shouldReceive('month')->zeroOrMoreTimes()->andReturn($month)->byDefault();
        $dateTime->shouldReceive('day')->zeroOrMoreTimes()->andReturn($day)->byDefault();
        $dateTime->shouldReceive('hour')->zeroOrMoreTimes()->andReturn($hour)->byDefault();
        $dateTime->shouldReceive('minute')->zeroOrMoreTimes()->andReturn($minute)->byDefault();
        $dateTime->shouldReceive('second')->zeroOrMoreTimes()->andReturn($second)->byDefault();
        $dateTime->shouldReceive('millisecond')->zeroOrMoreTimes()->andReturn($millisecond)->byDefault();
        $dateTime->shouldReceive('unixTimestampMs')->zeroOrMoreTimes()->andReturn($timestamp)->byDefault();
        $dateTime->shouldReceive('unixTimestamp')->zeroOrMoreTimes()->andReturn((int) floor($timestamp / 1000.0))->byDefault();
        return $dateTime;
    }

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

    public static function gregorianComponentsWithTimestamps(): iterable
    {
        $utc = TimeZone::lookup("UTC");
        yield "typical" => [2025, Month::April, 23, 13, 21, 7, 165, $utc, 1745414467165];

        $timestamps = [
            1609470848883, 1359676363901,   // January 1/31
            1612149248883, 1362095563901,   // February 1/28
            1614568448883, 1364773963901,   // March 1/31
            1617246848883, 1367365963901,   // April 1/30
            1619838848883, 1370044363901,   // May 1/31
            1622517248883, 1372636363901,   // June 1/30
            1625109248883, 1375314763901,   // July 1/31
            1627787648883, 1377993163901,   // August 1/31
            1630466048883, 1380585163901,   // September 1/30
            1633058048883, 1383263563901,   // October 1/31
            1635736448883, 1385855563901,   // November 1/30
            1638328448883, 1388533963901,   // December 1/31
        ];

        foreach (Month::cases() as $month) {
            yield "first-{$month->name}" => [2021, $month, 1, 3, 14, 8, 883, $utc, array_shift($timestamps)];
            yield "last-{$month->name}" => [2013, $month, $month->dayCount(2025), 23, 52, 43, 901, $utc, array_shift($timestamps)];
        }

        $timestamps = [
            742780800000, 859683599999,     // Hour 00
            742784400000, 859687199999,
            742788000000, 859690799999,
            742791600000, 859694399999,
            742795200000, 859697999999,
            742798800000, 859701599999,     // Hour 05
            742802400000, 859705199999,
            742806000000, 859708799999,
            742809600000, 859712399999,
            742813200000, 859715999999,
            742816800000, 859719599999,     // Hour 10
            742820400000, 859723199999,
            742824000000, 859726799999,
            742827600000, 859730399999,
            742831200000, 859733999999,
            742834800000, 859737599999,     // Hour 15
            742838400000, 859741199999,
            742842000000, 859744799999,
            742845600000, 859748399999,
            742849200000, 859751999999,
            742852800000, 859755599999,     // Hour 20
            742856400000, 859759199999,
            742860000000, 859762799999,
            742863600000, 859766399999,     // Hour 23
        ];

        for ($hour = 0; $hour < 24; ++$hour) {
            yield sprintf("start-hour-%02d", $hour) => [1993, Month::July, 16, $hour, 0, 0, 0, $utc, array_shift($timestamps)];
            yield sprintf("end-hour-%02d", $hour) => [1997, Month::March, 30, $hour, 59, 59, 999, $utc, array_shift($timestamps)];
        }

        $timestamps = [
            621612000000, 350506859999, 621612060000, 350506919999, 621612120000, 350506979999,     // Minutes 00-02
            621612180000, 350507039999, 621612240000, 350507099999, 621612300000, 350507159999,
            621612360000, 350507219999, 621612420000, 350507279999, 621612480000, 350507339999,
            621612540000, 350507399999, 621612600000, 350507459999, 621612660000, 350507519999,
            621612720000, 350507579999, 621612780000, 350507639999, 621612840000, 350507699999,
            621612900000, 350507759999, 621612960000, 350507819999, 621613020000, 350507879999,     // Minutes 15-17
            621613080000, 350507939999, 621613140000, 350507999999, 621613200000, 350508059999,
            621613260000, 350508119999, 621613320000, 350508179999, 621613380000, 350508239999,
            621613440000, 350508299999, 621613500000, 350508359999, 621613560000, 350508419999,
            621613620000, 350508479999, 621613680000, 350508539999, 621613740000, 350508599999,
            621613800000, 350508659999, 621613860000, 350508719999, 621613920000, 350508779999,     // Minutes 30-32
            621613980000, 350508839999, 621614040000, 350508899999, 621614100000, 350508959999,
            621614160000, 350509019999, 621614220000, 350509079999, 621614280000, 350509139999,
            621614340000, 350509199999, 621614400000, 350509259999, 621614460000, 350509319999,
            621614520000, 350509379999, 621614580000, 350509439999, 621614640000, 350509499999,
            621614700000, 350509559999, 621614760000, 350509619999, 621614820000, 350509679999,     // Minutes 45-47
            621614880000, 350509739999, 621614940000, 350509799999, 621615000000, 350509859999,
            621615060000, 350509919999, 621615120000, 350509979999, 621615180000, 350510039999,
            621615240000, 350510099999, 621615300000, 350510159999, 621615360000, 350510219999,
            621615420000, 350510279999, 621615480000, 350510339999, 621615540000, 350510399999,
        ];

        for ($minute = 0; $minute < 60; ++$minute) {
            yield sprintf("start-minute-%02d", $minute) => [1989, Month::September, 12, 14, $minute, 0, 0, $utc, array_shift($timestamps)];
            yield sprintf("end-minute-%02d", $minute) => [1981, Month::February, 8, 19, $minute, 59, 999, $utc, array_shift($timestamps)];
        }

        $timestamps = [
            -597874380000, -99142019001, -597874379000, -99142018001, -597874378000, -99142017001,      // Seconds 00-02
            -597874377000, -99142016001, -597874376000, -99142015001, -597874375000, -99142014001,
            -597874374000, -99142013001, -597874373000, -99142012001, -597874372000, -99142011001,
            -597874371000, -99142010001, -597874370000, -99142009001, -597874369000, -99142008001,
            -597874368000, -99142007001, -597874367000, -99142006001, -597874366000, -99142005001,
            -597874365000, -99142004001, -597874364000, -99142003001, -597874363000, -99142002001,      // Seconds 15-07
            -597874362000, -99142001001, -597874361000, -99142000001, -597874360000, -99141999001,
            -597874359000, -99141998001, -597874358000, -99141997001, -597874357000, -99141996001,
            -597874356000, -99141995001, -597874355000, -99141994001, -597874354000, -99141993001,
            -597874353000, -99141992001, -597874352000, -99141991001, -597874351000, -99141990001,
            -597874350000, -99141989001, -597874349000, -99141988001, -597874348000, -99141987001,      // Seconds 30-32
            -597874347000, -99141986001, -597874346000, -99141985001, -597874345000, -99141984001,
            -597874344000, -99141983001, -597874343000, -99141982001, -597874342000, -99141981001,
            -597874341000, -99141980001, -597874340000, -99141979001, -597874339000, -99141978001,
            -597874338000, -99141977001, -597874337000, -99141976001, -597874336000, -99141975001,
            -597874335000, -99141974001, -597874334000, -99141973001, -597874333000, -99141972001,      // Seconds 45-47
            -597874332000, -99141971001, -597874331000, -99141970001, -597874330000, -99141969001,
            -597874329000, -99141968001, -597874328000, -99141967001, -597874327000, -99141966001,
            -597874326000, -99141965001, -597874325000, -99141964001, -597874324000, -99141963001,
            -597874323000, -99141962001, -597874322000, -99141961001, -597874321000, -99141960001,
        ];

        for ($second = 0; $second < 60; ++$second) {
            yield "start-second-" . sprintf("%02d", $second) => [1951, Month::January, 21, 3, 47, $second, 0, $utc, array_shift($timestamps)];
            yield "end-second-" . sprintf("%02d", $second) => [1966, Month::November, 10, 12, 33, $second, 999, $utc, array_shift($timestamps)];
        }
// TODO get timestamps for Gregorian dates in these timezones to enable tests
////        yield "timezone-europe-london" => [1975, Month::May, 18, 5, 28, 14, 311, new DateTimeZone("Europe/London")];
//        yield "timezone-+0400" => [2001, Month::October, 23, 22, 01, 19, 170, TimeZone::parse("+0400")];
//        yield "timezone--0330" => [2001, Month::March, 23, 22, 01, 19, 170, TimeZone::parse("-0330")];
    }

    /** DateTimes each paired with a second DateTime whose Gregorian properties are after the first. */
    public static function dateTimesAndLaterDateTimes(): iterable
    {
        // 2025-02-25T19:19:09.464Z <=> 2025-02-25T19:19:09.465Z
        yield "gregorian-1-millisecond-later" => [1740511149464, 1740511149465];

        // 1968-05-19T18:03:44.000Z <=> 1968-05-19T18:03:45.000Z
        yield "gregorian-1-second-later" => [-51083776000, -51083775000];

        // 2014-12-25T19:21:16.000Z <=> 2014-12-25T19:22:16.000Z
        yield "gregorian-1-minute-later" => [1419535276000, 1419535336000];

        // 2005-09-12T10:19:27.000Z <=> 2005-09-12T11:19:27.000Z
        yield "gregorian-1-hour-later" => [1126520367000, 1126523967000];

        // 2011-08-23T17:37:19.000Z <=> 2011-08-24T17:37:19.000Z
        yield "gregorian-1-day-later" => [1314121039000, 1314207439000];

        // 2001-07-03T06:41:25.000Z <=> 2001-08-03T06:41:25.000Z
        yield "gregorian-1-month-later" => [994142485000, 996820885000];

        // 1992-06-11T09:28:09.000Z <=> 1993-06-11T09:28:09.000Z
        yield "gregorian-1-year-later" => [708254889000, 739790889000];
    }

    /** Timestamps paired with a second timestamp whose Gregorian properties are before the first. */
    public static function dateTimesAndEarlierDateTimes(): iterable
    {
        // 2025-02-25T19:29:58.043Z <=> 2025-02-25T19:29:58.042Z
        yield "gregorian-1-millisecond-earlier" => [1740511798043, 1740511798042];

        // 2029-04-28T11:31:53.000Z <=> 2029-04-28T11:31:52.000Z
        yield "gregorian-1-second-earlier" => [1872070313000, 1872070312000];

        // 2008-03-10T23:35:43.000Z <=> 2008-03-10T23:34:43.000Z
        yield "gregorian-1-minute-earlier" => [1205192143000, 1205192083000];

        // 1931-05-14T18:21:46.000Z <=> 1931-05-14T17:21:46.000Z
        yield "gregorian-1-hour-earlier" => [-1219210694000, -1219214294000];

        // 1942-08-19T13:18:26.000Z <=> 1942-08-18T13:18:26.000Z
        yield "gregorian-1-day-earlier" => [-863692894000, -863779294000];

        // 1955-09-10T21:22:21.000Z <=> 1955-08-10T21:22:21.000Z
        yield "gregorian-1-month-earlier" => [-448943859000, -451535859000];

        // 1963-02-15T10:00:38.000Z <=> 1962-02-15T10:00:38.000Z
        yield "gregorian-1-year-earlier" => [-217000762000, -248536762000];
    }

    /**
     * Timestamps paired with a second alternative DateTime contract implementation whose Gregorian properties are
     * after the first.
     */
    public static function dateTimesAndLaterAlternativeDateTimes(): iterable
    {
        /** @var int[] $arguments */
        foreach (self::dateTimesAndLaterDateTimes() as $key => $arguments) {
            $second = DateTime::fromUnixTimestampMs($arguments[1]);

            yield "alternative-{$key}" => [
                $arguments[0],
                self::mockDateTime(
                    $second->year(),
                    $second->month(),
                    $second->day(),
                    $second->hour(),
                    $second->minute(),
                    $second->second(),
                    $second->millisecond(),
                    $second->unixTimestampMs(),
                ),
            ];
        }
    }

    /**
     * Timestamps paired with a second alternative DateTime contract implementation whose Gregorian properties are
     * before the first.
     */
    public static function dateTimesAndEarlierAlternativeDateTimes(): iterable
    {
        /** @var DateTime[] $arguments */
        foreach (self::dateTimesAndEarlierDateTimes() as $key => $arguments) {
            $second = DateTime::fromUnixTimestampMs($arguments[1]);

            yield "alternative-{$key}" => [
                $arguments[0],
                self::mockDateTime(
                    $second->year(),
                    $second->month(),
                    $second->day(),
                    $second->hour(),
                    $second->minute(),
                    $second->second(),
                    $second->millisecond(),
                    $second->unixTimestampMs(),
                ),
            ];
        }
    }

    /**
     * Ensure we can create accurate DateTime instances from date-time components.
     *
     * NOTE the timestamp from the data provider is intentionally ignored.
     */
    #[DataProvider("gregorianComponentsWithTimestamps")]
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


    public static function phpDateTimeImmutables(): iterable
    {
        $timezoneName = "UTC";
//        foreach (["America/New_York", "Europe/London"] as $timezoneName) {
            $timezone = new PhpDateTimeZone($timezoneName);

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
                yield "every-millisecond-{$timezoneName}-1965-12-01-01:21:18.{$millisecond}" => [
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
//        }
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
        self::assertSame($expectedTimeZone, $actual->timeZone()->name());
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
        self::assertSame($expectedTimeZone, $actual->timeZone()->name());
    }

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

    /** Ensure leap years are detected correctly. */
    #[DataProvider("leapYears")]
    public function testIsLeapYear1(int $year): void
    {
        self::assertTrue((new StaticXRay(DateTime::class))->isLeapYear($year));
    }

    /** Ensure non-leap years are detected correctly. */
    #[DataProvider("nonLeapYears")]
    public function testIsLeapYear2(int $year): void
    {
        self::assertFalse((new StaticXRay(DateTime::class))->isLeapYear($year));
    }

    /** Ensure a DateTime is Gregorian-clean when constructed from Gregorian date-time properties. */
    public function testIsGregorianClean1(): void
    {
        $dateTime = DateTime::create( 2023, Month::March, 21,10, 21, 44);
        self::assertTrue((new XRay($dateTime))->isGregorianClean());
    }

    /** Ensure a DateTime is not Gregorian-clean when constructed from a timestamp. */
    public function testIsGregorianClean2(): void
    {
        // 2019-09-12T20:35:19.000Z
        $dateTime = DateTime::fromUnixTimestamp(1568320519);
        self::assertFalse((new XRay($dateTime))->isGregorianClean());
    }

    /** Ensure a DateTime is not Gregorian-clean when constructed from a milliseconds timestamp. */
    public function testIsGregorianClean3(): void
    {
        // 2020-09-07T22:37:28.681Z
        $dateTime = DateTime::fromUnixTimestampMs(1599518248681);
        self::assertFalse((new XRay($dateTime))->isGregorianClean());
    }

    /** Ensure a DateTime is Gregorian-clean when set from Gregorian date-time properties. */
    public function testIsGregorianClean4(): void
    {
        // 2025-02-24T23:32:14.000Z
        $dateTime = DateTime::fromUnixTimestamp(1740439934);
        self::assertFalse((new XRay($dateTime))->isGregorianClean());
        $dateTime = $dateTime->withDate(2025, Month::April, 14)->withTime(15, 8, 31);
        self::assertTrue((new XRay($dateTime))->isGregorianClean());
    }

    /** Ensure a DateTime is not Gregorian-clean when a timezone is set. */
    public function testIsGregorianClean5(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 26, 20, 19, 52, 123);
        self::assertTrue((new XRay($dateTime))->isGregorianClean());
        self::assertNotSame("Europe/London", $dateTime->timeZone()->name());
        $dateTime = $dateTime->withTimeZone(TimeZone::lookup("Europe/London"));
        self::assertFalse((new XRay($dateTime))->isGregorianClean());
    }

    /** Ensure clones retain the original's properties. */
    public function testClone1(): void
    {
        // 2025-02-25T19:07:48.000Z
        $expected = DateTime::fromUnixTimestamp(1740510468);
        $timeZone = $expected->timeZone();
        $actual = clone $expected;
        self::assertSame($expected->unixTimestamp(), $actual->unixTimestamp());
        self::assertSame($expected->unixTimestampMs(), $actual->unixTimestampMs());
        self::assertSame($expected->year(), $actual->year());
        self::assertSame($expected->month(), $actual->month());
        self::assertSame($expected->day(), $actual->day());
        self::assertSame($expected->hour(), $actual->hour());
        self::assertSame($expected->minute(), $actual->minute());
        self::assertSame($expected->second(), $actual->second());
        self::assertSame($expected->millisecond(), $actual->millisecond());
        self::assertSame($timeZone->name(), $actual->timeZone()->name());
    }

    /** Ensure cloning clones the timezone to retain immutability. */
    public function testClone2(): void
    {
        // 2025-02-25T19:11:48.000Z
        $expected = DateTime::fromUnixTimestamp(1740510708);
        $actual = clone $expected;
        self::assertNotSame($expected->timeZone(), $actual->timeZone());
    }

    /** Ensure comparison result is 0 for identical Gregorian date-time properties. */
    public function testCompareGregorian1(): void
    {
        // 2025-02-25T19:14:55.000Z
        $first = new XRay(DateTime::fromUnixTimestampMs(1740510895448));
        $first->syncGregorian();
        $second = DateTime::fromUnixTimestampMs(1740510895448);
        self::assertSame(0, $first->compareGregorian($second));
    }

    /** Ensure comparison result is < 0 when comparing to later Gregorian date-times. */
    #[DataProvider("dateTimesAndLaterDateTimes")]
    public function testCompareGregorian2(int $firstUnix, int $secondUnix): void
    {
        $first = new XRay(DateTime::fromUnixTimestampMs($firstUnix));
        $first->syncGregorian();
        self::assertLessThan(0, $first->compareGregorian(DateTime::fromUnixTimestampMs($secondUnix)));
    }

    /** Ensure comparison result is > 0 when comparing to earlier Gregorian date-times. */
    #[DataProvider("dateTimesAndEarlierDateTimes")]
    public function testCompareGregorian3(int $firstUnix, int $secondUnix): void
    {
        $first = new XRay(DateTime::fromUnixTimestampMs($firstUnix));
        $first->syncGregorian();
        self::assertGreaterThan(0, $first->compareGregorian(DateTime::fromUnixTimestampMs($secondUnix)));
    }

    /** Ensure Gregorian comparisons work as expected with other equal DateTime contract implementations */
    public function testCompareGregorian4(): void
    {
        // 2025-02-25T22:01:53.000Z
        $first = new XRay(DateTime::fromUnixTimestamp(1740520913));
        $first->syncGregorian();
        $second = self::mockDateTime(2025, Month::February, 25, 22, 1, 53, 0);
        self::assertSame(0, $first->compareGregorian($second));
    }

    /**
     * Ensure comparison result is < 0 when comparing to other DateTime contract implementations' later Gregorian
     * date-times.
     */
    #[DataProvider("dateTimesAndLaterAlternativeDateTimes")]
    public function testCompareGregorian5(int $firstUnix, DateTimeContract $other): void
    {
        $first = new XRay(DateTime::fromUnixTimestampMs($firstUnix));
        $first->syncGregorian();
        self::assertLessThan(0, $first->compareGregorian($other));
    }

    /**
     * Ensure comparison result is > 0 when comparing to other DateTime contract implementations' later Gregorian
     * date-times.
     */
    #[DataProvider("dateTimesAndEarlierAlternativeDateTimes")]
    public function testCompareGregorian6(int $firstUnix, DateTimeContract $other): void
    {
        $first = new XRay(DateTime::fromUnixTimestampMs($firstUnix));
        $first->syncGregorian();
        self::assertGreaterThan(0, $first->compareGregorian($other));
    }

    /** Ensure comparison result is 0 for identical Unix timestamps. */
    public function testCompareUnix1(): void
    {
        // 2025-02-25T22:25:07.737Z
        $first = DateTime::fromUnixTimestampMs(1740522307737);
        $second = DateTime::fromUnixTimestampMs(1740522307737);
        self::assertSame(0, (new XRay($first))->compareUnix($second));
    }

    /** Ensure comparison result is > 0 when comparing to later Unix date-times. */
    #[DataProvider("dateTimesAndLaterDateTimes")]
    public function testCompareUnix2(int $firstUnix, int $secondUnix): void
    {
        self::assertLessThan(0, (new XRay(DateTime::fromUnixTimestampMs($firstUnix)))->compareUnix(DateTime::fromUnixTimestampMs($secondUnix)));
    }

    /** Ensure comparison result is < 0 when comparing to earlier Unix date-times. */
    #[DataProvider("dateTimesAndEarlierDateTimes")]
    public function testCompareUnix3(int $firstUnix, int $secondUnix): void
    {
        self::assertGreaterThan(0, (new XRay(DateTime::fromUnixTimestampMs($firstUnix)))->compareUnix(DateTime::fromUnixTimestampMs($secondUnix)));
    }

    /** Ensure Unix comparisons work as expected with other equal DateTime contract implementations */
    public function testCompareUnix4(): void
    {
        // 2025-02-25T22:01:53.000Z
        $first = DateTime::fromUnixTimestampMs(1740520913);
        $second = self::mockDateTime(timestamp: 1740520913);
        self::assertSame(0, (new XRay($first))->compareUnix($second));
    }

    /**
     * Ensure comparison result is < 0 when comparing to other DateTime contract implementations' later Unix date-times.
     */
    #[DataProvider("dateTimesAndLaterAlternativeDateTimes")]
    public function testCompareUnix5(int $firstUnix, DateTimeContract $other): void
    {
        self::assertLessThan(0, (new XRay(DateTime::fromUnixTimestampMs($firstUnix)))->compareUnix($other));
    }

    /**
     * Ensure comparison result is > 0 when comparing to other DateTime contract implementations' later Unix timestamps.
     */
    #[DataProvider("dateTimesAndEarlierAlternativeDateTimes")]
    public function testCompareUnix6(int $firstUnix, DateTimeContract $other): void
    {
        self::assertGreaterThan(0, (new XRay(DateTime::fromUnixTimestampMs($firstUnix)))->compareUnix($other));
    }

    /** Ensure DateTime created from Unix timestamp is Unix clean. */
    public function testIsUnixClean1(): void
    {
        self::assertTrue((new XRay(DateTime::fromUnixTimestampMs(1740525077)))->isUnixClean());
    }

    /** Ensure DateTime created from Unix millisecond timestamp is Unix clean. */
    public function testIsUnixClean2(): void
    {
        self::assertTrue((new XRay(DateTime::fromUnixTimestampMs(1740525115623)))->isUnixClean());
    }

    /** Ensure DateTime created from Gregorian components is not Unix clean. */
    public function testIsUnixClean3(): void
    {
        self::assertFalse((new XRay(DateTime::create(2025, Month::February, 25, 23, 13, 51, 841)))->isUnixClean());
    }

    /** Ensure DateTime created from Gregorian components without time is not Unix clean. */
    public function testIsUnixClean4(): void
    {
        self::assertFalse((new XRay(DateTime::create(2025, Month::March, 1)))->isUnixClean());
    }

    /** Ensure DateTime with modified Gregorian date is not Unix clean. */
    public function testIsUnixClean5(): void
    {
        // 2025-02-25T23:19:04.000Z
        $dateTime = DateTime::fromUnixTimestampMs(1740525544701)
            ->withDate(2024, Month::April, 4);

        self::assertFalse((new XRay($dateTime))->isUnixClean());
    }

    /** Ensure DateTime with modified Gregorian time is not Unix clean. */
    public function testIsUnixClean6(): void
    {
        // 2025-02-25T23:23:17.000Z
        $dateTime = DateTime::fromUnixTimestampMs(1740525797045)
            ->withTime(13, 8, 4, 8);

        self::assertFalse((new XRay($dateTime))->isUnixClean());
    }

    /** Ensure DateTime with modified Gregorian hour, minute and second is not Unix clean. */
    public function testIsUnixClean7(): void
    {
        // 2025-02-25T23:24:17.622Z
        $dateTime = DateTime::fromUnixTimestampMs(1740525857622)
            ->withTime(10, 32, 18);

        self::assertFalse((new XRay($dateTime))->isUnixClean());
    }

    /** Ensure DateTime with modified Gregorian hour and minute is not Unix clean. */
    public function testIsUnixClean8(): void
    {
        // 2025-02-25T23:25:29.923Z
        $dateTime = DateTime::fromUnixTimestampMs(1740525929923)
            ->withTime(9, 41, 55);

        self::assertFalse((new XRay($dateTime))->isUnixClean());
    }

    /** Ensure the DateTime is Unix clean after Unix synchronisation. */
    public function testIsUnixClean9(): void
    {
        $dateTime = new XRay(DateTime::create(2025, Month::February, 25));
        self::assertFalse($dateTime->isUnixClean());
        $dateTime->syncUnix();
        self::assertTrue($dateTime->isUnixClean());
    }

    /** Ensure syncUnix() correctly synchronises the Unix timestamp. */
    #[DataProvider("gregorianComponentsWithTimestamps")]
    public function testSyncUnix(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, \Meridiem\Contracts\TimeZone $timeZone, int $expectedTimestampMs): void
    {
        $dateTime = new XRay(DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone));
        self::assertFalse($dateTime->isUnixClean());
        $dateTime->syncUnix();
        self::assertSame($expectedTimestampMs, $dateTime->unixMs, "Expected {$expectedTimestampMs}, found {$dateTime->unixMs}");
    }

    // TODO millisecondsBeforeEpoch()
    // TODO millisecondsAfterEpoch()
    // TODO syncUnix()
    // TODO syncGregorianDecrementHour()
    // TODO syncGregorianIncrementHour()
    // TODO syncGregorianPostEpoch()
    // TODO syncGregorianPreEpoch()
    // TODO syncGregorian()
    // TODO now()
    // TODO withDate()
    // TODO year()
    // TODO month()
    // TODO day()
    // TODO hour()
    // TODO minute()
    // TODO second()
    // TODO millisecond()
    // TODO weekday()
    // TODO withTime()
    // TODO withHour()
    // TODO hour()
    // TODO withMinute()
    // TODO minute()
    // TODO withSecond()
    // TODO second()
    // TODO withMillisecond()
    // TODO millisecond()
    // TODO unixTimestamp()
    // TODO unixTimestampMs()
    // TODO withTimeZone()
    // TODO timeZone()
    // TODO isBefore()
    // TODO isAfter()
    // TODO isEqualTo()
    // TODO isInSameYearAs()
    // TODO isInSameMonthAs()
    // TODO isOnSameDayAs()
    // TODO isInSameHourAs()
    // TODO isInSameMinuteAs()
    // TODO isInSameSecondAs()
}
