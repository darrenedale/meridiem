<?php

namespace MeridiemTests;

use DateTimeInterface as PhpDateTimeInterface;
use DateTime as PhpDateTime;
use DateTimeImmutable as PhpDateTimeImmutable;
use DateTimeZone as PhpDateTimeZone;
use Equit\XRay\StaticXRay;
use Equit\XRay\XRay;
use LogicException;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\DateTime;
use Meridiem\GregorianRatios;
use Meridiem\Month;
use InvalidArgumentException;
use Meridiem\TimeZone;
use Meridiem\UnixEpoch;
use Meridiem\Weekday;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

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
        $dateTime->allows("year")->andReturn($year)->byDefault();
        $dateTime->allows("month")->andReturn($month)->byDefault();
        $dateTime->allows("day")->andReturn($day)->byDefault();
        $dateTime->allows("hour")->andReturn($hour)->byDefault();
        $dateTime->allows("minute")->andReturn($minute)->byDefault();
        $dateTime->allows("second")->andReturn($second)->byDefault();
        $dateTime->allows("millisecond")->andReturn($millisecond)->byDefault();
        $dateTime->allows("unixTimestampMs")->andReturn($timestamp)->byDefault();
        $dateTime->allows("unixTimestamp")->andReturn((int) floor($timestamp / 1000.0))->byDefault();
        return $dateTime;
    }

    /** A selection of years that are not valid. */
    public static function invalidYears(): iterable
    {
        yield "too-small" => [-10000];
        yield "too-large" => [10000];
        yield "min-int" => [PHP_INT_MIN];
        yield "max-int" => [PHP_INT_MAX];
    }

    /** A selection of valid years. */
    public static function validYears(): iterable
    {
        yield "year-min" => [-9999];
        yield "year-max" => [9999];
        yield "zero" => [0];

        for ($year = 1; $year <= 2100; ++$year) {
            yield sprintf("year-%04d", $year) => [$year];
        }
    }

    /** A selection of leap years. */
    public static function leapYears(): iterable
    {
        for ($year = 1904; $year < 2100; $year += 4) {
            yield sprintf("leap-year-%04d", $year) => [$year];
        }

        for ($year = 2104; $year < 2200; $year += 4) {
            yield sprintf("leap-year-%04d", $year) => [$year];
        }

        for ($year = 2204; $year < 2300; $year += 4) {
            yield sprintf("leap-year-%04d", $year) => [$year];
        }

        for ($year = 2304; $year <= 2400; $year += 4) {
            yield sprintf("leap-year-%04d", $year) => [$year];
        }
    }

    /** A selection of non-leap years. */
    public static function nonLeapYears(): iterable
    {
        yield "non-leap-year-1900" => [1900];
        $skip = 0;

        for ($year = 1904; $year < 2100; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-year-%04d", $year) => [$year];
        }

        yield "non-leap-year-2100" => [2100];
        $skip = 0;

        for ($year = 2104; $year < 2200; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-year-%04d", $year) => [$year];
        }

        yield "non-leap-year-2200" => [2200];
        $skip = 0;

        for ($year = 2204; $year < 2300; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-year-%04d", $year) => [$year];
        }

        yield "non-leap-year-2300" => [2300];
        $skip = 0;

        for ($year = 2304; $year <= 2400; ++$year) {
            if (0 === ($skip++ % 4)) {
                continue;
            }

            yield sprintf("non-leap-year-%04d", $year) => [$year];
        }
    }

    /** All the Month enumeration cases. */
    public static function allMonths(): iterable
    {
        foreach (Month::cases() as $month) {
            yield sprintf("month-%02d-%s", $month->value, $month->name) => [$month];
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
            yield sprintf("millisecond-%02d", $millisecond) => [$millisecond];
        }
    }

    /** The month and day for every day of a leap year. */
    public static function everyLeapYearDay(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day < $month->dayCount(2000); ++$day) {
                yield sprintf("leap-year-day-%02d-%s-%02d", $month->value, $month->name, $day) => [2000, $month, $day];
            }
        }
    }

    /** The month and day for every day of a non-leap year. */
    public static function everyNonLeapYearDay(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day < $month->dayCount(2025); ++$day) {
                yield sprintf("non-leap-year-day-%02d-%s-%02d", $month->value, $month->name, $day) => [2025, $month, $day];
            }
        }
    }

    /** Some Gregorian date-times and their equivalent Unix millisecond timestamps. */
    public static function gregorianComponentsWithMillisecondTimestamps(): iterable
    {
        $utc = TimeZone::lookup("UTC");
        yield "typical-ms-timestamp" => [2025, Month::April, 23, 13, 21, 7, 165, $utc, 1745414467165];

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
            yield "first-{$month->name}-ms-timestamp" => [2021, $month, 1, 3, 14, 8, 883, $utc, array_shift($timestamps)];
            yield "last-{$month->name}-ms-timestamp" => [2013, $month, $month->dayCount(2025), 23, 52, 43, 901, $utc, array_shift($timestamps)];
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
            yield sprintf("start-hour-%02d-ms-timestamp", $hour) => [1993, Month::July, 16, $hour, 0, 0, 0, $utc, array_shift($timestamps)];
            yield sprintf("end-hour-%02d-ms-timestamp", $hour) => [1997, Month::March, 30, $hour, 59, 59, 999, $utc, array_shift($timestamps)];
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
            yield sprintf("start-minute-%02d-ms-timestamp", $minute) => [1989, Month::September, 12, 14, $minute, 0, 0, $utc, array_shift($timestamps)];
            yield sprintf("end-minute-%02d-ms-timestamp", $minute) => [1981, Month::February, 8, 19, $minute, 59, 999, $utc, array_shift($timestamps)];
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
            yield sprintf("start-second-%02d-ms-timestamp", $second) => [1951, Month::January, 21, 3, 47, $second, 0, $utc, array_shift($timestamps)];
            yield sprintf("end-second-%02d-ms-timestamp", $second) => [1966, Month::November, 10, 12, 33, $second, 999, $utc, array_shift($timestamps)];
        }
//        yield "timezone-europe-london" => [1975, Month::May, 18, 5, 28, 14, 311, new DateTimeZone("Europe/London")];
        yield "timezone-+0400-ms-timestamp" => [2001, Month::October, 23, 22, 01, 19, 170, TimeZone::parse("+0400"), 1003860079170];
        yield "timezone--0330-ms-timestamp" => [2001, Month::March, 23, 22, 01, 19, 170, TimeZone::parse("-0330", ), 985397479170];
    }

    /** Some Gregorian date-times and their equivalent Unix timestamps. */
    public static function gregorianComponentsWithTimestamps(): iterable
    {
        foreach (self::gregorianComponentsWithMillisecondTimestamps() as $key => $arguments) {
            $arguments[8] = (int) floor($arguments[8] / 1000.0);
            yield str_replace("-ms-timestamp", "-timestamp", $key) => $arguments;
        }
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

    /** Unix millisecond timestamps paired with others that are not the same. */
    public static function unequalUnixMillisecondTimestamps(): iterable
    {
        yield from self::dateTimesAndEarlierDateTimes();
        yield from self::dateTimesAndLaterDateTimes();
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

    /** DateTime instances paired with other contract implementations that are not equal. */
    public static function dateTimesAndUnequalOtherImplementations(): iterable
    {
        yield from self::dateTimesAndEarlierAlternativeDateTimes();
        yield from self::dateTimesAndLaterAlternativeDateTimes();
    }

    /** A selection of Gregorian date components with invalid days. */
    public static function gregorianComponentsInvalidDays(): iterable
    {
        foreach ([2000, 2025] as $year) {
            foreach (Month::cases() as $month) {
                yield "{$year}-{$month->name}-negative" => [$year, $month, -1];
                yield "{$year}-{$month->name}-zero" => [$year, $month, 0];
                yield "{$year}-{$month->name}-too-high" => [$year, $month, $month->dayCount($year) + 1];
            }
        }
    }

    /** Gregorian components for all valid days in a non-leap year.*/
    public static function validGregorianComponentsNonLeapYear(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day <= $month->dayCount(2025); $day++) {
                yield "{$day}-{$month->name}" => [$month, $day];
            }
        }
    }

    /** Gregorian hour and minute components for all valid times of day. */
    public static function allValidGregorianHoursAndMinutes(): iterable
    {
        foreach (self::validHours() as $hour) {
            foreach (self::validMinutes() as $minute) {
                yield "{$hour[0]}:{$minute[0]}}" => [$hour[0], $minute[0]];
            }
        }
    }

    /** Gregorian date components for all valid days in a leap year.*/
    public static function validGregorianComponentsLeapYear(): iterable
    {
        foreach (Month::cases() as $month) {
            for ($day = 1; $day <= $month->dayCount(2000); $day++) {
                yield "{$day}-{$month->name}" => [$month, $day];
            }
        }
    }

    /** Unix timestamps and their equivalent (UTC) Gregorian date-times. */
    public static function unixTimestamps(): iterable
    {
        yield "unix-epoch" => [0, 1970, Month::January, 1, 0, 0, 0];
        yield "unix-one-second-before-epoch" => [-1, 1969, Month::December, 31, 23, 59, 59];
        yield "unix-one-second" => [1, 1970, Month::January, 1, 0, 0, 1];
        yield "unix-leap-year" => [951826154, 2000, Month::February, 29, 12, 9, 14];
    }

    /** Unix timestamps and their millisecond equivalents. */
    public static function unixTimestampsAndMillisecondTimestamps(): iterable
    {
        foreach (self::unixTimestamps() as $key => $arguments) {
            yield str_replace("unix-", "unix-ms-", $key) => [$arguments[0], $arguments[0] * 1000];
        }
    }

    /** Unix millisecond timestamps and their second equivalents. */
    public static function unixMillisecondTimestampsAndTimestamps(): iterable
    {
        yield "unix-ms-epoch" => [0, 0];
        yield "unix-ms-one-second-before-epoch" => [-1000, -1];
        yield "unix-ms-one-millisecond-before-epoch" => [-1, -1];
        yield "unix-ms-one-second" => [1000, 1];
        yield "unix-ms-one-millisecond" => [1, 0];
        yield "unix-ms-pre-epoch-milliseconds" => [-1001, -2];
        yield "unix-ms-post-epoch-milliseconds" => [951826154123, 951826154];
    }

    /** Unix millisecond timestamps. */
    public static function unixMillisecondTimestamps(): iterable
    {
        yield "unix-ms-epoch" => [0];
        yield "unix-ms-one-second-before-epoch" => [-1000];
        yield "unix-ms-one-millisecond-before-epoch" => [-1];
        yield "unix-ms-one-second" => [1000];
        yield "unix-ms-one-millisecond" => [1];
        yield "unix-ms-pre-epoch-milliseconds" => [-1001];
        yield "unix-ms-post-epoch-milliseconds" => [951826154123];
    }

    /** A selection of dates and the weekdays for them. */
    public static function datesAndWeekdays(): iterable
    {
        // a week"s worth of days and their weekdays, post-epoch
        yield "monday-2025-02-24" => [2025, Month::February, 24, Weekday::Monday];
        yield "tuesday-2025-02-25" => [2025, Month::February, 25, Weekday::Tuesday];
        yield "wednesday-2025-02-26" => [2025, Month::February, 26, Weekday::Wednesday];
        yield "thursday-2025-02-27" => [2025, Month::February, 27, Weekday::Thursday];
        yield "friday-2025-02-28" => [2025, Month::February, 28, Weekday::Friday];
        yield "saturday-2025-03-01" => [2025, Month::March, 1, Weekday::Saturday];
        yield "sunday-2025-03-02" => [2025, Month::March, 2, Weekday::Sunday];

        // a week"s worth of days and their weekdays, pre-epoch
        yield "monday-1953-09-07" => [1953, Month::September, 7, Weekday::Monday];
        yield "tuesday-1953-09-08" => [1953, Month::September, 8, Weekday::Tuesday];
        yield "wednesday-1953-09-09" => [1953, Month::September, 9, Weekday::Wednesday];
        yield "thursday-1953-09-10" => [1953, Month::September, 10, Weekday::Thursday];
        yield "friday-1953-09-11" => [1953, Month::September, 11, Weekday::Friday];
        yield "saturday-1953-09-12" => [1953, Month::September, 12, Weekday::Saturday];
        yield "sunday-1953-09-13" => [1953, Month::September, 13, Weekday::Sunday];
    }

    /** A large selection of PHP built-in DateTime objects. */
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

    /** A large selection of PHP built-in DateTimeImmutable objects. */
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

    /** Some dates paired with all dates in the same year. */
    public static function datesWithAllDatesInSameYear(): iterable
    {
        // 2025-04-01T12:00:00.000Z
        $testDate = 1743508800;

        // 2025-01-01T12:00:00.000Z
        $sameYear = 1735732800;

        for ($day = 1; $day <= 365; ++$day) {
            yield sprintf("after-epoch-2025-04-01-and-2025-day-%03d", $day) => [$testDate, $sameYear];
            $sameYear += GregorianRatios::SecondsPerHour * GregorianRatios::HoursPerDay;
        }

        // 1963-07-23T14:01:18.000Z
        $testDate = -203335122;

        // 1963-01-01T12:00:00.000Z
        $sameYear = -220874322;

        for ($day = 1; $day <= 365; ++$day) {
            yield sprintf("before-epoch-1963-07-23-and-1963-day-%03d", $day) => [$testDate, $sameYear];
            $sameYear += GregorianRatios::SecondsPerHour * GregorianRatios::HoursPerDay;
        }

        // 1984-02-29T01:18:42.000Z
        $testDate = 446865522;

        // 1984-01-01T12:00:00.000Z
        $sameYear = 441767922;

        for ($day = 1; $day <= 366; ++$day) {
            yield sprintf("leap-year-1984-07-23-and-1984-day-%03d", $day) => [$testDate, $sameYear];
            $sameYear += GregorianRatios::SecondsPerHour * GregorianRatios::HoursPerDay;
        }
    }

    /** A selection of days in the same month. */
    public static function daysInSameMonth(): iterable
    {
        $firstDays = [1, 8, 13, 15, 18, 25];
        $secondDays = [1, 5, 11, 16, 18, 21, 27];

        foreach ([2000, 1991, 1963] as $year) {
            foreach (Month::cases() as $month) {
                $lastDay = $month->dayCount($year);

                foreach ([...$firstDays, $lastDay] as $day) {
                    foreach ([...$secondDays, $lastDay] as $otherDay) {
                        yield sprintf("same-month-%04d-%02s-%02d-and-%04d-%02s-%02d", $year, $month->value, $day, $year, $month->value, $otherDay) => [$year, $month, $day, $otherDay];
                    }
                }
            }
        }
    }

    /** A selection of days not in the same month. */
    public static function daysInDifferentMonths(): iterable
    {
        $utc = TimeZone::lookup("UTC");

        // dates at the boundaries between consecutive months for a leap year, post-epoch year and pre-epoch year
        foreach ([2000, 1991, 1963] as $year) {
            foreach (Month::cases() as $month) {
                $first = DateTime::create($year, $month, $month->dayCount($year), 23, 59, 59, 999, $utc);

                if (Month::December === $month) {
                    $second = DateTime::create($year + 1, $month->next(), 1, 0, 0, 0, 0, $utc);
                } else {
                    $second = DateTime::create($year, $month->next(), 1, 0, 0, 0, 0, $utc);
                }

                yield sprintf("%04d-%s-and-%s", $year, $month->name, $month->next()->name) => [$first, $second];
            }
        }

        // same month, different year
        foreach (Month::cases() as $month) {
            yield sprintf("%s-in-%04d-and-%04d", $month->name, 1996, 1967) => [
                DateTime::create(1996, $month, 14, 12, 11, 6, 421, $utc),
                DateTime::create(1967, $month, 21, 23, 1, 51, 883, $utc),
            ];
        }
    }

    /** A selection of hours in the same day. */
    public static function hoursInSameDay(): iterable
    {
        $firstHours = [1, 8, 13, 15, 18, 23];
        $secondHours = [1, 5, 11, 16, 18, 21, 23];

        foreach ([2000, 1991, 1963] as $year) {
            foreach (Month::cases() as $month) {
                $lastDay = $month->dayCount($year);

                foreach ([1, 15, $lastDay] as $day) {
                    foreach ($firstHours as $firstHour) {
                        foreach ($secondHours as $secondHour) {
                            yield sprintf("same-day-%04d-%02s-%02d-%02dh-and-%04d-%02s-%02d-%02dh", $year, $month->value, $day, $firstHour, $year, $month->value, $day, $secondHour) => [$year, $month, $day, $firstHour, $secondHour, $secondHour];
                        }
                    }
                }
            }
        }
    }

    /** A selection of DateTimes in not in the same day. */
    public static function timesInDifferentDays(): iterable
    {
        $utc = TimeZone::lookup("UTC");

        /** @var Month[] $months */
        $months = [
            Month::January, Month::June, Month::May, Month::April, Month::May,
            Month::November, Month::March, Month::February, Month::April, Month::October,
            Month::November, Month::October, Month::March, Month::June, Month::February,
            Month::May, Month::March, Month::April, Month::December, Month::November,
            Month::March, Month::June, Month::September, Month::May, Month::June,
            Month::August, Month::September, Month::August, Month::December, Month::July,
            Month::December, Month::January,
        ];

        // times at the boundaries between consecutive days for a leap year, post-epoch year and pre-epoch year
        foreach ([2000, 1991, 1963] as $year) {
            for ($day = 1; $day < 31; ++$day) {
                $first = DateTime::create($year, $months[$day], $day, 23, 59, 59, 999, $utc);
                $second = DateTime::create($year, $months[$day], $day + 1, 0, 0, 0, 0, $utc);

                yield sprintf("%04d-%s-%02d-and-%02d", $year, $months[$day]->name, $day, $day + 1) => [$first, $second];
            }

            yield sprintf("%04d-December-31-and-January-01", $year) => [
                DateTime::create($year, Month::December, 31, 23, 59, 59, 999, $utc),
                DateTime::create($year + 1, Month::January, 1, 0, 0, 0, 0, $utc),
            ];
        }

        // same month and day, different year
        foreach (Month::cases() as $month) {
            yield sprintf("%02d-%s-in-%04d-and-%04d", 14, $month->name, 1996, 1967) => [
                DateTime::create(1996, $month, 14, 12, 11, 6, 421, $utc),
                DateTime::create(1967, $month, 14, 23, 1, 51, 883, $utc),
            ];
        }
    }

    /** Selection of date-times in the same hour. */
    public static function timesInSameHour(): iterable
    {
        $firstMinutes = [1, 7, 11, 18, 22, 31, 33, 42, 49, 54];
        $secondMinutes = [1, 5, 10, 15, 24, 30, 33, 40, 44, 51];

        /**
         * One month to use for each hour in the test data so that we cover a selection
         * @var Month[] $months
         */
        $months = [
            Month::October, Month::November, Month::March, Month::February, Month::June,
            Month::December, Month::March, Month::February, Month::October, Month::October,
            Month::January, Month::May, Month::June, Month::May, Month::April,
            Month::March, Month::May, Month::December, Month::April, Month::November,
            Month::June, Month::September, Month::June, Month::April,
        ];

        // One day to use for each hour in the test data so that we cover a selection
        $days = [
            14, 19, 31, 12, 16, 16, 13, 28, 31, 8,
            15, 13, 12, 23, 11, 24, 5, 7, 30, 9,
            23, 21, 1, 19,
        ];

        foreach ([1959, 1983] as $year) {
            for ($hour = 0; $hour < 24; ++$hour) {
                foreach ($firstMinutes as $firstMinute) {
                    foreach ($secondMinutes as $secondMinute) {
                        yield [$year, $months[$hour], $days[$hour], $hour, $firstMinute, $secondMinute];
                    }
                }
            }
        }
    }

    /** Selection of date-times not in the same hour. */
    public static function timesInDifferentHours(): iterable
    {
        $utc = TimeZone::lookup("UTC");

        /**
         * One month to use for each hour in the test data so that we cover a selection
         * @var Month[] $months
         */
        $months = [
            Month::January, Month::June, Month::May, Month::April, Month::May,
            Month::November, Month::March, Month::February, Month::April, Month::October,
            Month::November, Month::October, Month::March, Month::June, Month::February,
            Month::May, Month::March, Month::April, Month::December, Month::November,
            Month::March, Month::June, Month::September, Month::February,
        ];

        // One day to use for each hour in the test data so that we cover a selection
        $days = [
            14, 19, 31, 12, 16, 16, 13, 28, 30, 8,
            15, 13, 12, 23, 11, 24, 5, 7, 30, 9,
            23, 21, 1, 19,
        ];

        // times at the boundaries between consecutive hours for a post-epoch year and pre-epoch year
        foreach ([1982, 1961] as $year) {
            for ($hour = 0; $hour < 23; ++$hour) {
                $first = DateTime::create($year, $months[$hour], $days[$hour], $hour, 59, 59, 999, $utc);
                $second = DateTime::create($year, $months[$hour], $days[$hour], $hour + 1, 0, 0, 0, $utc);

                // end of 2300 and start of 0000 the next day
                yield sprintf("%04d-%s-%02d-%02d:59:59.999-and-%02d:00:00.000", $year, $months[$hour]->name, $days[$hour], $hour, $hour + 1) => [$first, $second];
            }

            yield sprintf("%04d-October-18-23:59:59.000-and-%04d-October-19-00:00:00.000", $year, $year + 1) => [
                DateTime::create($year, Month::October, 18, 23, 59, 59, 999, $utc),
                DateTime::create($year, Month::October, 19, 0, 0, 0, 0, $utc),
            ];
        }

        // leap year - last hour in 29th and first hour in 1st
        yield "leap-year-1996-02-29-23:59:59.999-and-1996-03-01-00:00:00.000" => [
            DateTime::create(1996, Month::February, 29, 23, 59, 59, 999, $utc),
            DateTime::create(1996, Month::March, 1, 0, 0, 0, 0, $utc),
        ];

        // leap year - last hour in 28th and first hour in 29th
        yield "leap-year-1988-02-28-23:59:59.999-and-1988-02-29-00:00:00.000" => [
            DateTime::create(1988, Month::February, 28, 23, 59, 59, 999, $utc),
            DateTime::create(1988, Month::February, 29, 0, 0, 0, 0, $utc),
        ];

        // same month, day and hour, different year
        foreach (Month::cases() as $month) {
            yield sprintf("%02d:00-%02d-%s-in-%04d-and-%04d", 19, 7, $month->name, 1948, 1979) => [
                DateTime::create(1948, $month, 7, 19, 23, 19, 630, $utc),
                DateTime::create(1979, $month, 7, 19, 44, 30, 750, $utc),
            ];
        }
    }

    /** Selection of date-times in the same minute. */
    public static function timesInSameMinute(): iterable
    {
        $firstSeconds = [1, 6, 12, 19, 23, 35, 38, 43, 48, 54];
        $secondSeconds = [1, 8, 13, 16, 22, 31, 34, 41, 42, 58];

        /**
         * One month to use for each minute in the test data so that we cover a selection.
         *
         * 20 months repeated 3 times to cover 60 minutes.
         *
         * @var Month[] $months
         */
        $months = [
            Month::October, Month::June, Month::January, Month::February, Month::February,
            Month::August, Month::August, Month::November, Month::December, Month::December,
            Month::November, Month::January, Month::November, Month::March, Month::February,
            Month::August, Month::November, Month::June, Month::August, Month::January,
        ];

        // One day to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $days = [
            23, 20, 23, 29, 28, 16, 2, 13, 10, 24,
            24, 19, 14, 19, 9, 17, 25, 30, 1, 13,
        ];

        // One hour to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $hours = [
            13, 18, 1, 20, 9, 15, 17, 18, 22, 11,
            10, 18, 5, 20, 0, 8, 10, 4, 18, 18,
        ];

        foreach ([1947, 2006] as $year) {
            for ($minute = 0; $minute < 60; $minute += 5) {
                foreach ($firstSeconds as $firstSecond) {
                    foreach ($secondSeconds as $secondSecond) {
                        yield [$year, $months[$minute % 20], $days[$minute % 20], $hours[$minute % 20], $minute, $firstSecond, $secondSecond];
                    }
                }
            }
        }
    }

    /** Selection of date-times not in the same minute. */
    public static function timesInDifferentMinutes(): iterable
    {
        $utc = TimeZone::lookup("UTC");

        /**
         * One month to use for each minute in the test data so that we cover a selection
         *
         * 20 months repeated 3 times to cover 60 minutes.
         *
         * @var Month[] $months
         */
        $months = [
            Month::January, Month::June, Month::May, Month::April, Month::May,
            Month::November, Month::March, Month::February, Month::April, Month::October,
            Month::November, Month::October, Month::March, Month::June, Month::February,
            Month::May, Month::March, Month::April, Month::December, Month::November,
        ];

        // One day to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $days = [
            8, 8, 10, 21, 8, 16, 17, 22, 19, 12,
            13, 19, 25, 13, 1, 29, 24, 21, 5, 18,
        ];

        // One hour to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $hours = [
            17, 16, 8, 11, 1, 20, 6, 1, 5, 6,
            12, 14, 9, 8, 17, 6, 17, 20, 22, 5,
        ];

        // times at the boundaries between consecutive minutes for a post-epoch year and pre-epoch year
        foreach ([1989, 1966] as $year) {
            for ($minute = 0; $minute < 59; ++$minute) {
                $first = DateTime::create($year, $months[$minute % 20], $days[$minute % 20], $hours[$minute % 20], $minute, 59, 999, $utc);
                $second = DateTime::create($year, $months[$minute % 20], $days[$minute % 20], $hours[$minute % 20], $minute + 1, 0, 0, $utc);

                // end of 2300 and start of 0000 the next day
                yield sprintf("%04d-%s-%02d-%02d:%02d:59.999-and-%02d:%02d:00.000", $year, $months[$minute % 20]->name, $days[$minute % 20], $hours[$minute % 20], $minute, $hours[$minute % 20], $minute + 1) => [$first, $second];
            }

            yield sprintf("%04d-March-9-23:59:59.000-and-%04d-March-10-00:00:00.000", $year, $year + 1) => [
                DateTime::create($year, Month::March, 9, 23, 59, 59, 999, $utc),
                DateTime::create($year, Month::March, 10, 0, 0, 0, 0, $utc),
            ];
        }

        // leap year - last minute in 29th and first minute in 1st
        yield "leap-year-1988-02-29-23:59:59.999-and-1988-03-01-00:00:00.000" => [
            DateTime::create(1988, Month::February, 29, 23, 59, 59, 999, $utc),
            DateTime::create(1988, Month::March, 1, 0, 0, 0, 0, $utc),
        ];

        // leap year - last minute in 28th and first minute in 29th
        yield "leap-year-1976-02-28-23:59:59.999-and-1976-02-29-00:00:00.000" => [
            DateTime::create(1976, Month::February, 28, 23, 59, 59, 999, $utc),
            DateTime::create(1976, Month::February, 29, 0, 0, 0, 0, $utc),
        ];

        // same month, day, hour and minute different year
        foreach (Month::cases() as $month) {
            yield sprintf("%02d:%02d-%s-in-%04d-and-%04d", 8, 38, $month->name, 1948, 1979) => [
                DateTime::create(1948, $month, 11, 8, 38, 51, 15, $utc),
                DateTime::create(1979, $month, 11, 8, 38, 5, 332, $utc),
            ];
        }
    }

    /** Selection of date-times in the same second. */
    public static function timesInSameSecond(): iterable
    {
        $firstMilliseconds = [
            0, 34, 88, 150, 226, 268, 328, 392, 447, 525,
            580, 663, 728, 813, 869, 899, 947, 999,
        ];

        $secondMilliseconds = [
            0, 64, 100, 172, 232, 270, 337, 387, 453, 521,
            577, 647, 715, 775, 827, 887, 948, 999,
        ];

        /**
         * One month to use for each second in the test data so that we cover a selection.
         *
         * 20 months repeated 3 times to cover 60 seconds.
         *
         * @var Month[] $months
         */
        $months = [
            Month::May, Month::November, Month::March, Month::December, Month::July,
            Month::February, Month::September, Month::November, Month::April, Month::September,
            Month::February, Month::April, Month::April, Month::May, Month::November,
            Month::February, Month::June, Month::July, Month::May, Month::July,
        ];

        // One day to use for each second in the test data so that we cover a selection (20 repeated 3 times)
        $days = [
            3, 14, 22, 28, 8, 23, 9, 19, 29, 12,
            10, 20, 20, 3, 7, 25, 28, 31, 2, 5,
        ];

        // One hour to use for each second in the test data so that we cover a selection (20 repeated 3 times)
        $hours = [
            20, 21, 23, 18, 12, 10, 18, 12, 17, 19,
            5, 18, 18, 2, 4, 8, 19, 7, 8, 7,
        ];

        // One minute to use for each second in the test data so that we cover a selection (20 repeated 3 times)
        $minutes = [
            24, 59, 34, 5, 56, 11, 30, 27, 20, 44,
            45, 57, 33, 48, 4, 12, 14, 16, 4, 32,
        ];

        // one pre- and one post-epoch year
        foreach ([1947, 2006] as $year) {
            for ($second = 0; $second < 60; ++$second) {
                foreach ($firstMilliseconds as $firstMillisecond) {
                    foreach ($secondMilliseconds as $secondMillisecond) {
                        yield [$year, $months[$second % 20], $days[$second % 20], $hours[$second % 20], $minutes[$second % 20], $second, $firstMillisecond, $secondMillisecond];
                    }
                }
            }
        }
    }

    /** Selection of date-times not in the same second. */
    public static function timesInDifferentSeconds(): iterable
    {
        $utc = TimeZone::lookup("UTC");

        /**
         * One month to use for each second in the test data so that we cover a selection
         * @var Month[] $months
         */
        $months = [
            Month::June, Month::January, Month::February, Month::April, Month::January,
            Month::February, Month::January, Month::December, Month::April, Month::June,
            Month::October, Month::February, Month::February, Month::June, Month::January,
            Month::June, Month::November, Month::January, Month::December, Month::November,
        ];

        // One day to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $days = [
            1, 23, 1, 7, 24, 9, 22, 6, 30, 27,
            27, 3, 23, 25, 21, 26, 5, 31, 14, 24,
        ];

        // One hour to use for each minute in the test data so that we cover a selection (20 repeated 3 times)
        $hours = [
            9, 9, 15, 22, 11, 10, 20, 20, 4, 7,
            16, 2, 7, 15, 15, 8, 15, 5, 3, 20,
        ];

        // One minute to use for each second in the test data so that we cover a selection (20 repeated 3 times)
        $minutes = [
            30, 42, 14, 38, 24, 59, 27, 44, 16, 39,
            49, 26, 30, 34, 56, 2, 47, 1, 20, 45,
        ];

        // times at the boundaries between consecutive seconds for a post-epoch year and pre-epoch year
        foreach ([1991, 1965] as $year) {
            for ($second = 0; $second < 59; ++$second) {
                $firstDateTime = DateTime::create($year, $months[$second % 20], $days[$second % 20], $hours[$second % 20], $minutes[$second % 20], $second, 999, $utc);
                $secondDateTime = DateTime::create($year, $months[$second % 20], $days[$second % 20], $hours[$second % 20], $minutes[$second % 20], $second + 1, 0, $utc);

                // end of 2300 and start of 0000 the next day
                yield sprintf("%04d-%s-%02d-%02d:%02d:%02d.999-and-%02d:%02d:%02d.000", $year, $months[$second % 20]->name, $days[$second % 20], $hours[$second % 20], $minutes[$second % 20], $second, $hours[$second % 20], $minutes[$second % 20], $second + 1) => [$firstDateTime, $secondDateTime];
            }

            yield sprintf("%04d-August-9-23:59:59.000-and-%04d-August-10-00:00:00.000", $year, $year + 1) => [
                DateTime::create($year, Month::August, 29, 23, 59, 59, 999, $utc),
                DateTime::create($year, Month::August, 30, 0, 0, 0, 0, $utc),
            ];
        }

        // leap year - last second in 29th and first second in 1st
        yield "leap-year-1988-02-29-23:59:59.999-and-1988-03-01-00:00:00.000" => [
            DateTime::create(1996, Month::February, 29, 23, 59, 59, 999, $utc),
            DateTime::create(1996, Month::March, 1, 0, 0, 0, 0, $utc),
        ];

        // leap year - last minute in 28th and first minute in 29th
        yield "leap-year-1976-02-28-23:59:59.999-and-1976-02-29-00:00:00.000" => [
            DateTime::create(1984, Month::February, 28, 23, 59, 59, 999, $utc),
            DateTime::create(1984, Month::February, 29, 0, 0, 0, 0, $utc),
        ];

        // same month, day, hour, minute and second, different year
        foreach (Month::cases() as $month) {
            yield sprintf("%02d:%02d:%02d-%s-in-%04d-and-%04d", 12, 1, 49, $month->name, 1933, 1989) => [
                DateTime::create(1933, $month, 21, 12, 1, 49, 15, $utc),
                DateTime::create(1989, $month, 21, 12, 1, 49, 332, $utc),
            ];
        }
    }

    /** Dates paired with other dates that are in the first ms of the next year. */
    public static function datesAndFirstMsOfNextYear(): iterable
    {
        yield "after-epoch-1ms-next-year" => [
            DateTime::create(2002, Month::May, 27, 21, 8, 30, 449),
            DateTime::create(2003, Month::January, 1, 0, 0, 59, 0),
        ];

        yield "before-epoch-1ms-next-year" => [
            DateTime::create(1955, Month::October, 15, 13, 0, 51, 280),
            DateTime::create(1956, Month::January, 1, 0, 0, 59, 0),
        ];

        yield "leap-year-1ms-next-year" => [
            DateTime::create(1992, Month::November, 19, 15, 42, 38, 033),
            DateTime::create(1993, Month::January, 1, 0, 0, 59, 0),
        ];
    }

    /** Dates paired with other dates that are in the last ms of the previous year. */
    public static function datesAndLastMsOfPreviousYear(): iterable
    {
        yield "after-epoch-1ms-previous-year" => [
            DateTime::create(2010, Month::December, 3, 19, 31, 32, 181),
            DateTime::create(2009, Month::December, 31, 23, 59, 59, 999),
        ];

        yield "before-epoch-1ms-previous-year" => [
            DateTime::create(1943, Month::August, 28, 17, 44, 1, 763),
            DateTime::create(1942, Month::December, 31, 23, 59, 59, 999),
        ];

        yield "leap-year-1ms-previous-year" => [
            DateTime::create(1974, Month::September, 24, 11, 44, 21, 915),
            DateTime::create(1973, Month::December, 31, 23, 59, 59, 999),
        ];
    }

    /** Dates paired with the same date in another year. */
    public static function datesInTwoDifferentYears(): iterable
    {
        for ($year = 1900; $year < 2040; ++$year) {
            // after epoch
            if (1984 !== $year) {
                yield sprintf("1984-03-18-14:03:41.941-and-%04d", $year) => [
                    DateTime::create(1984, Month::March, 18, 14, 3, 41, 941),
                    DateTime::create($year, Month::March, 18, 14, 3, 41, 941),
                ];
            }

            // before epoch
            if (1958 !== $year) {
                yield sprintf("1958-04-21-09:12:19.300-and-%04d", $year) => [
                    DateTime::create(1958, Month::April, 21, 9, 12, 19, 300),
                    DateTime::create($year, Month::April, 21, 9, 12, 19, 300),
                ];
            }
        }
    }

    /** Microtimes for testing DateTime::now(), and the expected DateTimes. */
    public static function microtimesAndDates(): iterable
    {
        yield "epoch" => [0.0, DateTime::create(1970, Month::January, 1, 0, 0, 0, 0)];

        // ensure microseconds are always rounded down to milliseconds
        yield "1-microseconds" => [1741474654.999001, DateTime::create(2025, Month::March, 8, 22, 57, 34, 999)];
        yield "499-microseconds" => [1567964614.500499, DateTime::create(2019, Month::September, 8, 17, 43, 34, 500)];
        yield "501-microseconds" => [190873957.000501, DateTime::create(1976, Month::January, 19, 4, 32, 37, 0)];
        yield "999-microseconds" => [373893251.158999, DateTime::create(1981, Month::November, 6, 11, 14, 11, 158)];
    }

    /**
     * Ensure we can create accurate DateTime instances from date-time components.
     *
     * NOTE the timestamp from the data provider is intentionally ignored.
     */
    #[DataProvider("gregorianComponentsWithMillisecondTimestamps")]
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

    /** Ensure all valid days in a non-leap-year can be created successfully. */
    #[DataProvider("validGregorianComponentsNonLeapYear")]
    public function testCreate4(Month $month, int $day): void
    {
        $actual = DateTime::create(2025, $month, $day);
        self::assertSame(2025, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
    }

    /** Ensure all valid days in a leap-year can be created successfully. */
    #[DataProvider("validGregorianComponentsLeapYear")]
    public function testCreate5(Month $month, int $day): void
    {
        $actual = DateTime::create(2000, $month, $day);
        self::assertSame(2000, $actual->year());
        self::assertSame($month, $actual->month());
        self::assertSame($day, $actual->day());
    }

    /** Ensure all valid times of day can be created successfully. */
    #[DataProvider("allValidGregorianHoursAndMinutes")]
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

    /** Ensure attempting to create with invalid days throws. */
    #[DataProvider("gregorianComponentsInvalidDays")]
    public function testCreate13(int $year, Month $month, int $day): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected day between 1 and {$month->dayCount($year)} inclusive, found {$day}");
        DateTime::create($year, $month, $day);
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

    /** Ensure we can correctly instantiate from unix timestamps. */
    #[DataProvider("unixTimestamps")]
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
    #[DataProvider("gregorianComponentsWithMillisecondTimestamps")]
    public function testSyncUnix1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, \Meridiem\Contracts\TimeZone $timeZone, int $expectedTimestampMs): void
    {
        $dateTime = new XRay(DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone));
        self::assertFalse($dateTime->isUnixClean());
        $dateTime->syncUnix();
         self::assertSame($expectedTimestampMs, $dateTime->unixMs, "Expected {$expectedTimestampMs}, found {$dateTime->unixMs}");
    }

    /** Ensure the day, month and year are set. */
    public function testWithDate1(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 27);
        // test is only valid if the date-time is initialised correctly
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::February, $dateTime->month());
        self::assertSame(27, $dateTime->day());
        $dateTime = $dateTime->withDate(2011, Month::July, 5);
        self::assertSame(2011, $dateTime->year());
        self::assertSame(Month::July, $dateTime->month());
        self::assertSame(5, $dateTime->day());
    }

    /** Ensure setting the date to invalid years throws. */
    #[DataProvider("invalidYears")]
    public function testWithDate2(int $year): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected year between -9999 and 9999 inclusive, found {$year}");

        DateTime::create(2025, Month::February, 27)
            ->withDate($year, Month::January, 1);
    }

    /** Ensure setting the date to invalid days throws. */
    #[DataProvider("gregorianComponentsInvalidDays")]
    public function testWithDate3(int $year, Month $month, int $day): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/^Expected day between 1 and (?:2[89]|3[01]) inclusive, found {$day}\$/");

        DateTime::create(2025, Month::February, 27)
            ->withDate($year, $month, $day);
    }

    /** Ensure setting the date preserves immutability. */
    public function testWithDate4(): void
    {
        $dateTime = DateTime::create(2021, Month::May, 13);
        $actual = $dateTime->withDate(2024, Month::February, 27);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(2021, $dateTime->year());
        self::assertSame(Month::May, $dateTime->month());
        self::assertSame(13, $dateTime->day());
    }

    /** Ensure the hour, minute, second and millisecond are set. */
    public function testWithTime1(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 27, 10, 4, 21, 831);
        // test is only valid if the date-time is initialised correctly
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::February, $dateTime->month());
        self::assertSame(27, $dateTime->day());
        self::assertSame(10, $dateTime->hour());
        self::assertSame(4, $dateTime->minute());
        self::assertSame(21, $dateTime->second());
        self::assertSame(831, $dateTime->millisecond());
        $dateTime = $dateTime->withTime(14, 29,33,110);
        self::assertSame(14, $dateTime->hour());
        self::assertSame(29, $dateTime->minute());
        self::assertSame(33, $dateTime->second());
        self::assertSame(110, $dateTime->millisecond());
    }

    /** Ensure the millisecond is set to 0 when the time is set without it. */
    public function testWithTime2(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 27, 10, 4, 21, 831);
        // test is only valid if the date-time is initialised correctly
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::February, $dateTime->month());
        self::assertSame(27, $dateTime->day());
        self::assertSame(10, $dateTime->hour());
        self::assertSame(4, $dateTime->minute());
        self::assertSame(21, $dateTime->second());
        self::assertSame(831, $dateTime->millisecond());
        $dateTime = $dateTime->withTime(14, 29,33);
        self::assertSame(14, $dateTime->hour());
        self::assertSame(29, $dateTime->minute());
        self::assertSame(33, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
    }

    /** Ensure the second and millisecond are both set to 0 when the time is set without them. */
    public function testWithTime3(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 27, 10, 4, 21, 831);
        // test is only valid if the date-time is initialised correctly
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::February, $dateTime->month());
        self::assertSame(27, $dateTime->day());
        self::assertSame(10, $dateTime->hour());
        self::assertSame(4, $dateTime->minute());
        self::assertSame(21, $dateTime->second());
        self::assertSame(831, $dateTime->millisecond());
        $dateTime = $dateTime->withTime(14, 29);
        self::assertSame(14, $dateTime->hour());
        self::assertSame(29, $dateTime->minute());
        self::assertSame(0, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
    }

    /** Ensure setting the time to an invalid hour throws. */
    #[DataProvider("invalidHours")]
    public function testWithTime4(int $hour): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected hour between 0 and 23 inclusive, found {$hour}");

        DateTime::create(2025, Month::February, 27, 10, 4, 21, 831)
            ->withTime($hour, 10, 15, 20);
    }

    /** Ensure setting the time to an invalid minute throws. */
    #[DataProvider("invalidMinutes")]
    public function testWithTime5(int $minute): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected minute between 0 and 59 inclusive, found {$minute}");

        DateTime::create(2025, Month::February, 27, 10, 4, 21, 831)
            ->withTime(10, $minute, 15, 20);
    }

    /** Ensure setting the time to an invalid second throws. */
    #[DataProvider("invalidSeconds")]
    public function testWithTime6(int $second): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected second between 0 and 59 inclusive, found {$second}");

        DateTime::create(2025, Month::February, 27, 10, 4, 21, 831)
            ->withTime(10, 15, $second, 20);
    }

    /** Ensure setting the time to an invalid millisecond throws. */
    #[DataProvider("invalidMilliseconds")]
    public function testWithTime7(int $millisecond): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected millisecond between 0 and 999 inclusive, found {$millisecond}");

        DateTime::create(2025, Month::February, 27, 10, 4, 21, 831)
            ->withTime(10, 15, 20, $millisecond);
    }

    /** Ensure setting the time preserves immutability. */
    public function testWithTime8(): void
    {
        $dateTime = DateTime::create(2025, Month::February, 27, 10, 4, 21, 831);
        $actual = $dateTime->withTime(3, 55, 28, 431);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(10, $dateTime->hour());
        self::assertSame(4, $dateTime->minute());
        self::assertSame(21, $dateTime->second());
        self::assertSame(831, $dateTime->millisecond());
    }

    /** Ensure we can read the timezone. */
    public function testTimeZone1(): void
    {
        // 2025-03-04T20:04:37.000Z
        $dateTime = DateTime::create(2025, Month::March, 4, 20, 4, 37, 0, TimeZone::parse("+0200"));
        $actual = $dateTime->timeZone();

        self::assertSame("+0200", $actual->name());
        $offset = $actual->sdtOffset();
        self::assertSame(2, $offset->hours());
        self::assertSame(0, $offset->minutes());
    }

    /** Ensure the correct year is reported by year(). */
    #[DataProvider("validYears")]
    public function testYear1(int $year): void
    {
        $dateTime = DateTime::create($year, Month::January, 1);
        self::assertSame($year, $dateTime->year());
    }

    /** Ensure the correct month is reported by month(). */
    #[DataProvider("allMonths")]
    public function testMonth1(Month $month): void
    {
        $dateTime = DateTime::create(2025, $month, 1);
        self::assertSame($month, $dateTime->month());
    }

    /** Ensure the correct day is reported by day() for all days in a non-leap year. */
    #[DataProvider("everyNonLeapYearDay")]
    public function testDay1(int $year, Month $month, int $day): void
    {
        $dateTime = DateTime::create($year, $month, $day);
        self::assertSame($day, $dateTime->day());
    }

    /** Ensure the correct day is reported by day() for all days in a leap year. */
    #[DataProvider("everyLeapYearDay")]
    public function testDay2(int $year, Month $month, int $day): void
    {
        $dateTime = DateTime::create($year, $month, $day);
        self::assertSame($day, $dateTime->day());
    }

    /** Ensure the correct hour is reported by hour(). */
    #[DataProvider("validHours")]
    public function testHour1(int $hour): void
    {
        $dateTime = DateTime::create(2001, Month::June, 2, $hour, 15);
        self::assertSame($hour, $dateTime->hour());
    }

    /** Ensure the correct minute is reported by minute(). */
    #[DataProvider("validMinutes")]
    public function testMinute1(int $minute): void
    {
        $dateTime = DateTime::create(2010, Month::August, 14, 12, $minute);
        self::assertSame($minute, $dateTime->minute());
    }

    /** Ensure the correct second is reported by second(). */
    #[DataProvider("validSeconds")]
    public function testSecond1(int $second): void
    {
        $dateTime = DateTime::create(1999, Month::March, 3, 5, 43, $second);
        self::assertSame($second, $dateTime->second());
    }

    /** Ensure the correct millisecond is reported by millisecond(). */
    #[DataProvider("validMilliseconds")]
    public function testMillisecond1(int $millisecond): void
    {
        $dateTime = DateTime::create(1993, Month::September, 23, 10, 18, 57, $millisecond);
        self::assertSame($millisecond, $dateTime->millisecond());
    }

    /** Ensure the weekday of the Epoch day is correct all day. */
    public function testWeekday1(): void
    {
        $dateTime = DateTime::fromUnixTimestamp(0);
        self::assertSame(Weekday::Thursday, $dateTime->weekday());
        $dateTime = DateTime::fromUnixTimestampMs(GregorianRatios::MillisecondsPerDay - 1);
        self::assertSame(Weekday::Thursday, $dateTime->weekday());
    }

    /** Ensure the weekday of the day after the Epoch day is correct. */
    public function testWeekday2(): void
    {
        $dateTime = DateTime::fromUnixTimestampMs(GregorianRatios::MillisecondsPerDay);
        self::assertSame(Weekday::Friday, $dateTime->weekday());
    }

    /** Ensure 29th February in a leap year has the correct weekday all day. */
    public function testWeekday3(): void
    {
        // 2000-02-29T00:00:00.000Z
        $dateTime = DateTime::fromUnixTimestamp(951782400);
        self::assertSame(Weekday::Tuesday, $dateTime->weekday());

        // 2000-02-29T23:59:59.999Z
        $dateTime = DateTime::fromUnixTimestampMs(951868799999);
        self::assertSame(Weekday::Tuesday, $dateTime->weekday());
    }

    /** Ensure 1st March in a leap year has the correct weekday all day. */
    public function testWeekday4(): void
    {
        // 2000-03-01T00:00:00.000Z
        $dateTime = DateTime::fromUnixTimestamp(951868800);
        self::assertSame(Weekday::Wednesday, $dateTime->weekday());
    }

    /** Ensure a selection of dates all have the correct weekday. */
    #[DataProvider("datesAndWeekdays")]
    public function testWeekday5(int $year, Month $month, int $day, Weekday $expectedWeekday): void
    {
        $dateTime = DateTime::create($year, $month, $day);
        self::assertSame($expectedWeekday, $dateTime->weekday());
        $dateTime = DateTime::create($year, $month, $day, 23, 59, 59, 999);
        self::assertSame($expectedWeekday, $dateTime->weekday());
    }

    /** Ensure we get the expected timestamp when creating from a timestamp. */
    #[DataProvider("unixTimestamps")]
    public function testUnixTimestamp1(int $timestamp): void
    {
        self::assertSame($timestamp, DateTime::fromUnixTimestamp($timestamp)->unixTimestamp());
    }

    /** Ensure we get the expected timestamp when creating from a millisecond timestamp. */
    #[DataProvider("unixMillisecondTimestampsAndTimestamps")]
    public function testUnixTimestamp2(int $msTimestamp, int $expectedTimestamp): void
    {
        self::assertSame($expectedTimestamp, DateTime::fromUnixTimestampMs($msTimestamp)->unixTimestamp());
    }

    /** Ensure we get the expected timestamp from Gregorian components. */
    #[DataProvider("gregorianComponentsWithTimestamps")]
    public function testUnixTimestamp3(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, TimeZone $timeZone, int $expectedTimestamp): void
    {
        self::assertSame(
            $expectedTimestamp,
            DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone)
                ->unixTimestamp(),
        );
    }

    /** Ensure we get the expected millisecond timestamp when creating from a timestamp. */
    #[DataProvider("unixMillisecondTimestamps")]
    public function testUnixTimestampMs1(int $millisecondTimestamp): void
    {
        self::assertSame($millisecondTimestamp, DateTime::fromUnixTimestampMs($millisecondTimestamp)->unixTimestampMs());
    }

    /** Ensure we get the expected millisecond timestamp when creating from a timestamp. */
    #[DataProvider("unixTimestampsAndMillisecondTimestamps")]
    public function testUnixTimestampMs2(int $timestamp, int $expectedTimestampMs): void
    {
        self::assertSame($expectedTimestampMs, DateTime::fromUnixTimestamp($timestamp)->unixTimestampMs());
    }

    /** Ensure we get the expected millisecond timestamp from Gregorian components. */
    #[DataProvider("gregorianComponentsWithMillisecondTimestamps")]
    public function testUnixTimestampMs3(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, TimeZone $timeZone, int $expectedTimestampMs): void
    {
        self::assertSame(
            $expectedTimestampMs,
            DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone)
                ->unixTimestampMs(),
        );
    }

    /** Ensure withHour() sets the correct hour. */
    #[DataProvider("validHours")]
    public function testWithHour1(int $hour): void
    {
        // ensure it starts out with a different hour
        $initialHour = ($hour + 6) % 24;
        $dateTime = DateTime::create(2025, Month::October, 12, $initialHour, 22);
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::October, $dateTime->month());
        self::assertSame(12, $dateTime->day());
        self::assertSame($initialHour, $dateTime->hour());
        self::assertSame(22, $dateTime->minute());
        self::assertSame(0, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
        $dateTime = $dateTime->withHour($hour);
        self::assertSame(2025, $dateTime->year());
        self::assertSame(Month::October, $dateTime->month());
        self::assertSame(12, $dateTime->day());
        self::assertSame($hour, $dateTime->hour());
        self::assertSame(22, $dateTime->minute());
        self::assertSame(0, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
    }

    /** Ensure withHour() preserves immutability. */
    public function testWithHour2(): void
    {
        $dateTime = DateTime::create(1942, Month::December, 24, 13, 1, 48, 992);
        $actual = $dateTime->withHour(4);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(1942, $dateTime->year());
        self::assertSame(Month::December, $dateTime->month());
        self::assertSame(24, $dateTime->day());
        self::assertSame(13, $dateTime->hour());
        self::assertSame(1, $dateTime->minute());
        self::assertSame(48, $dateTime->second());
        self::assertSame(992, $dateTime->millisecond());
    }

    /** Ensure withHour() throws when given an invalid minute. */
    #[DataProvider("invalidHours")]
    public function testWithHour3(int $hour): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected hour between 0 and 23 inclusive, found {$hour}");
        DateTime::create(1986, Month::April, 26, 1, 23)
            ->withHour($hour);
    }

    /** Ensure withMinute() sets the correct minute. */
    #[DataProvider("validMinutes")]
    public function testWithMinute1(int $minute): void
    {
        // ensure it starts out with a different minute
        $initialMinute = ($minute + 30) % 60;
        $dateTime = DateTime::create(2019, Month::July, 3, 4, $initialMinute);
        self::assertSame(2019, $dateTime->year());
        self::assertSame(Month::July, $dateTime->month());
        self::assertSame(3, $dateTime->day());
        self::assertSame(4, $dateTime->hour());
        self::assertSame($initialMinute, $dateTime->minute());
        self::assertSame(0, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
        $dateTime = $dateTime->withMinute($minute);
        self::assertSame(2019, $dateTime->year());
        self::assertSame(Month::July, $dateTime->month());
        self::assertSame(3, $dateTime->day());
        self::assertSame(4, $dateTime->hour());
        self::assertSame($minute, $dateTime->minute());
        self::assertSame(0, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
    }

    /** Ensure withMinute() preserves immutability. */
    public function testWithMinute2(): void
    {
        $dateTime = DateTime::create(1987, Month::May, 15, 8 , 48, 7, 577);
        $actual = $dateTime->withMinute(4);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(1987, $dateTime->year());
        self::assertSame(Month::May, $dateTime->month());
        self::assertSame(15, $dateTime->day());
        self::assertSame(8, $dateTime->hour());
        self::assertSame(48, $dateTime->minute());
        self::assertSame(7, $dateTime->second());
        self::assertSame(577, $dateTime->millisecond());
    }

    /** Ensure withMinute() throws when given an invalid minute. */
    #[DataProvider("invalidMinutes")]
    public function testWithMinute3(int $minute): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected minute between 0 and 59 inclusive, found {$minute}");
        DateTime::create(1976, Month::February, 21, 9, 41)
            ->withMinute($minute);
    }

    /** Ensure withSecond() sets the correct second. */
    #[DataProvider("validSeconds")]
    public function testWithSecond1(int $second): void
    {
        // ensure it starts out with a different minute
        $initialSecond = ($second + 30) % 60;
        $dateTime = DateTime::create(2008, Month::November, 21, 21, 8, $initialSecond);
        self::assertSame(2008, $dateTime->year());
        self::assertSame(Month::November, $dateTime->month());
        self::assertSame(21, $dateTime->day());
        self::assertSame(21, $dateTime->hour());
        self::assertSame(8, $dateTime->minute());
        self::assertSame($initialSecond, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
        $dateTime = $dateTime->withSecond($second);
        self::assertSame(2008, $dateTime->year());
        self::assertSame(Month::November, $dateTime->month());
        self::assertSame(21, $dateTime->day());
        self::assertSame(21, $dateTime->hour());
        self::assertSame(8, $dateTime->minute());
        self::assertSame($second, $dateTime->second());
        self::assertSame(0, $dateTime->millisecond());
    }

    /** Ensure withSecond() preserves immutability. */
    public function testWithSecond2(): void
    {
        $dateTime = DateTime::create(1969, Month::March, 3, 18 , 33, 17, 141);
        $actual = $dateTime->withSecond(53);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(1969, $dateTime->year());
        self::assertSame(Month::March, $dateTime->month());
        self::assertSame(3, $dateTime->day());
        self::assertSame(18, $dateTime->hour());
        self::assertSame(33, $dateTime->minute());
        self::assertSame(17, $dateTime->second());
        self::assertSame(141, $dateTime->millisecond());
    }

    /** Ensure withSecond() throws when given an invalid second. */
    #[DataProvider("invalidSeconds")]
    public function testWithSecond3(int $second): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected second between 0 and 59 inclusive, found {$second}");
        DateTime::create(1974, Month::June, 5, 22, 59)
            ->withSecond($second);
    }

    /** Ensure withMillisecond() sets the correct millisecond. */
    #[DataProvider("validMilliseconds")]
    public function testWithMillisecond1(int $millisecond): void
    {
        // ensure it starts out with a different minute
        $initialMillisecond = ($millisecond + 500) % 1000;
        $dateTime = DateTime::create(1989, Month::August, 14, 10, 49, 28, $initialMillisecond);
        self::assertSame(1989, $dateTime->year());
        self::assertSame(Month::August, $dateTime->month());
        self::assertSame(14, $dateTime->day());
        self::assertSame(10, $dateTime->hour());
        self::assertSame(49, $dateTime->minute());
        self::assertSame(28, $dateTime->second());
        self::assertSame($initialMillisecond, $dateTime->millisecond());
        $dateTime = $dateTime->withMillisecond($millisecond);
        self::assertSame(1989, $dateTime->year());
        self::assertSame(Month::August, $dateTime->month());
        self::assertSame(14, $dateTime->day());
        self::assertSame(10, $dateTime->hour());
        self::assertSame(49, $dateTime->minute());
        self::assertSame(28, $dateTime->second());
        self::assertSame($millisecond, $dateTime->millisecond());
    }

    /** Ensure withMillisecond() preserves immutability. */
    public function testWithMillisecond2(): void
    {
        $dateTime = DateTime::create(1963, Month::January, 30, 23 , 4, 31, 835);
        $actual = $dateTime->withMillisecond(401);
        self::assertNotSame($dateTime, $actual);
        self::assertSame(1963, $dateTime->year());
        self::assertSame(Month::January, $dateTime->month());
        self::assertSame(30, $dateTime->day());
        self::assertSame(23, $dateTime->hour());
        self::assertSame(4, $dateTime->minute());
        self::assertSame(31, $dateTime->second());
        self::assertSame(835, $dateTime->millisecond());
    }

    /** Ensure withMillisecond() throws when given an invalid millisecond. */
    #[DataProvider("invalidMilliseconds")]
    public function testWithMillisecond3(int $millisecond): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected millisecond between 0 and 999 inclusive, found {$millisecond}");
        DateTime::create(1937, Month::May, 17, 3, 31, 18, 392)
            ->withMillisecond($millisecond);
    }

    /** Ensure a time zone can be set. */
    public function testWithTimeZone1(): void
    {
        // 2025-03-04T20:04:37.000Z
        $dateTime = DateTime::fromUnixTimestamp(1741118677);
        self::assertNotSame("+0200", $dateTime->timeZone()->name());
        $timeZone = TimeZone::parse("+0200");
        $dateTime = $dateTime->withTimeZone($timeZone);
        self::assertSame($timeZone->name(), $dateTime->timeZone()->name());
        self::assertSame($timeZone->sdtOffset()->hours(), $dateTime->timeZone()->sdtOffset()->hours());
        self::assertSame($timeZone->sdtOffset()->minutes(), $dateTime->timeZone()->sdtOffset()->minutes());
    }

    /** Ensure setting a time zone is immutable. */
    public function testWithTimeZone3(): void
    {
        // 2025-03-04T20:04:37.000Z
        $dateTime = DateTime::fromUnixTimestamp(1741118677);
        self::assertNotSame("+0200", $dateTime->timeZone()->name());
        $expectedYear = $dateTime->year();
        $expectedMonth = $dateTime->month();
        $expectedDay = $dateTime->day();
        $expectedHour = $dateTime->hour();
        $expectedMinute = $dateTime->minute();
        $expectedSecond = $dateTime->second();
        $expectedMillisecond = $dateTime->millisecond();
        $expectedTimeZoneName = $dateTime->timeZone()->name();
        $expectedTimeZoneOffset = $dateTime->timeZone()->sdtOffset();

        $actual = $dateTime->withTimeZone(TimeZone::parse("+0200"));

        self::assertNotSame($dateTime, $actual);
        self::assertSame($expectedTimeZoneName, $dateTime->timeZone()->name());
        self::assertSame($expectedTimeZoneOffset, $dateTime->timeZone()->sdtOffset());
        self::assertSame($expectedYear, $dateTime->year());
        self::assertSame($expectedMonth, $dateTime->month());
        self::assertSame($expectedDay, $dateTime->day());
        self::assertSame($expectedHour, $dateTime->hour());
        self::assertSame($expectedMinute, $dateTime->minute());
        self::assertSame($expectedSecond, $dateTime->second());
        self::assertSame($expectedMillisecond, $dateTime->millisecond());
    }

    /** Ensure we can correctly identify a DateTime is before another. */
    #[DataProvider("dateTimesAndLaterDateTimes")]
    public function testIsBefore1(int $firstUnixMilliseconds, int $secondUnixMilliseconds): void
    {
        self::assertTrue(
            DateTime::fromUnixTimestampMs($firstUnixMilliseconds)
                ->isBefore(DateTime::fromUnixTimestampMs($secondUnixMilliseconds))
        );
    }

    /** Ensure we can correctly identify a DateTime is not before another. */
    #[DataProvider("dateTimesAndEarlierDateTimes")]
    public function testIsBefore2(int $firstUnixMilliseconds, int $secondUnixMilliseconds): void
    {
        self::assertFalse(
            DateTime::fromUnixTimestampMs($firstUnixMilliseconds)
                ->isBefore(DateTime::fromUnixTimestampMs($secondUnixMilliseconds))
        );
    }

    /** Ensure DateTime instances are not before themselves. */
    #[DataProvider("unixMillisecondTimestamps")]
    public function testIsBefore3(int $unixMilliseconds): void
    {
        self::assertFalse(
            DateTime::fromUnixTimestampMs($unixMilliseconds)
                ->isBefore(DateTime::fromUnixTimestampMs($unixMilliseconds))
        );
    }

    /** Ensure we can correctly identify a DateTime is before another. */
    #[DataProvider("dateTimesAndEarlierDateTimes")]
    public function testIsAfter1(int $firstUnixMilliseconds, int $secondUnixMilliseconds): void
    {
        self::assertTrue(
            DateTime::fromUnixTimestampMs($firstUnixMilliseconds)
                ->isAfter(DateTime::fromUnixTimestampMs($secondUnixMilliseconds))
        );
    }

    /** Ensure we can correctly identify a DateTime is not after another. */
    #[DataProvider("dateTimesAndLaterDateTimes")]
    public function testIsAfter2(int $firstUnixMilliseconds, int $secondUnixMilliseconds): void
    {
        self::assertFalse(
            DateTime::fromUnixTimestampMs($firstUnixMilliseconds)
                ->isAfter(DateTime::fromUnixTimestampMs($secondUnixMilliseconds))
        );
    }

    /** Ensure DateTime instances are not after themselves. */
    #[DataProvider("unixMillisecondTimestamps")]
    public function testIsAfter3(int $unixMilliseconds): void
    {
        self::assertFalse(
            DateTime::fromUnixTimestampMs($unixMilliseconds)
                ->isAfter(DateTime::fromUnixTimestampMs($unixMilliseconds))
        );
    }

    /** Ensure we can correctly identify a DateTime is equal to another. */
    #[DataProvider("gregorianComponentsWithMillisecondTimestamps")]
    public function testIsEqualTo1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, TimeZone $timeZone, int $unixTimestampMs): void
    {
        self::assertTrue(
            DateTime::fromUnixTimestampMs($unixTimestampMs)
                ->isEqualTo(DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone))
        );
    }

    /** Ensure we can identify DateTime instances are not equal to others. */
    #[DataProvider("unequalUnixMillisecondTimestamps")]
    public function testIsEqualTo2(int $firstUnixMilliseconds, int $secondUnixMilliseconds): void
    {
        self::assertFalse(
            DateTime::fromUnixTimestampMs($firstUnixMilliseconds)
                ->isEqualTo(DateTime::fromUnixTimestampMs($secondUnixMilliseconds))
        );
    }

    /** Ensure we can identify DateTime instances are not equal to others of a different implementation. */
    #[DataProvider("dateTimesAndUnequalOtherImplementations")]
    public function testIsEqualTo3(int $firstUnixMs, DateTimeContract $second): void
    {
        self::assertFalse(DateTime::fromUnixTimestampMs($firstUnixMs)->isEqualTo($second));
    }

    /** Ensure a date is in the same year as all dates in that year. */
    #[DataProvider("datesWithAllDatesInSameYear")]
    public function testIsInSameYearAs1(int $testUnixTimestamp, int $sameYearUnixTimestamp): void
    {
        self::assertTrue(DateTime::fromUnixTimestamp($testUnixTimestamp)->isInSameYearAs(DateTime::fromUnixTimestamp($sameYearUnixTimestamp)));
    }

    /** Ensure dates that are not in the same year are detected correctly. */
    #[DataProvider("datesAndFirstMsOfNextYear")]
    #[DataProvider("datesAndLastMsOfPreviousYear")]
    #[DataProvider("datesInTwoDifferentYears")]
    public function testIsInSameYearAs2(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isInSameYearAs($second));
    }

    /**
     * Ensure now() uses the current microtime.
     *
     * TODO use Mokkd when available.
     */
    #[DataProvider("microtimesAndDates")]
    public function testNow1(float $seconds, DateTime $expected): void
    {
        $cleanup = static fn() => uopz_unset_return("microtime");

        $microtime = static function(mixed $arg) use ($seconds): float
        {
            DateTimeTest::assertSame(true, $arg);
            return $seconds;
        };

        $guard = new class($cleanup)
        {
            private $closure;

            public function __construct(callable $closure)
            {
                $this->closure = $closure;
            }

            public function __destruct()
            {
                ($this->closure)();
            }
        };

        uopz_set_return("microtime", $microtime, true);
        $actual = DateTime::now();
        self::assertSame($expected->unixTimestamp(), $actual->unixTimestamp());
        self::assertSame($expected->unixTimestampMs(), $actual->unixTimestampMs());
        self::assertSame($expected->year(), $actual->year());
        self::assertSame($expected->month(), $actual->month());
        self::assertSame($expected->day(), $actual->day());
        self::assertSame($expected->hour(), $actual->hour());
        self::assertSame($expected->minute(), $actual->minute());
        self::assertSame($expected->second(), $actual->second());
        self::assertSame($expected->millisecond(), $actual->millisecond());
    }

    /** Ensure days in the same month are detected correctly. */
    #[DataProvider("daysInSameMonth")]
    public function testIsInSameMonthAs1(int $year, Month $month, int $day, int $otherDay): void
    {
        $utc = TimeZone::lookup("UTC");

        self::assertTrue(DateTime::create($year, $month, $day, 3, 48, 11, 0, $utc)->isInSameMonthAs(DateTime::create($year, $month, $otherDay, 12, 1, 51, 0, $utc)));
        self::assertTrue(DateTime::create($year, $month, $otherDay, 21, 17, 30, 0, $utc)->isInSameMonthAs(DateTime::create($year, $month, $day, 19, 15, 37, 0, $utc)));
    }

    /** Ensure days at start and end of same month are detected correctly. */
    #[DataProvider("allMonths")]
    public function testIsInSameMonthAs2(Month $month): void
    {
        $utc = TimeZone::lookup("UTC");

        # pre-epoch
        self::assertTrue(DateTime::create(1955, $month, 1, 0, 0, 0, 0)->isInSameMonthAs(DateTime::create(1955, $month, $month->dayCount(1955), 0, 0, 0, 0, $utc)));

        # post-epoch
        self::assertTrue(DateTime::create(1981, $month, 1, 0, 0, 0, 0)->isInSameMonthAs(DateTime::create(1981, $month, $month->dayCount(1981), 0, 0, 0, 0, $utc)));

        # leap-year
        self::assertTrue(DateTime::create(1988, $month, 1, 0, 0, 0, 0)->isInSameMonthAs(DateTime::create(1988, $month, $month->dayCount(1988), 0, 0, 0, 0, $utc)));
    }

    /** Ensure days in different month are detected correctly. */
    #[DataProvider("daysInDifferentMonths")]
    public function testIsInSameMonthAs3(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isInSameMonthAs($second));
    }

    /** Ensure times in the same day are detected correctly. */
    #[DataProvider("hoursInSameDay")]
    public function testIsInSameDayAs1(int $year, Month $month, int $day, int $hour, int $otherHour): void
    {
        $utc = TimeZone::lookup("UTC");

        // arbitrary times in the same day
        self::assertTrue(DateTime::create($year, $month, $day, $hour, 28, 31, 22, $utc)->isOnSameDayAs(DateTime::create($year, $month, $day, $otherHour, 9, 23, 3, $utc)));
        self::assertTrue(DateTime::create($year, $month, $day, $otherHour, 17, 30, 0, $utc)->isOnSameDayAs(DateTime::create($year, $month, $day, $hour, 15, 37, 0, $utc)));
    }

    /** Ensure times in different days are detected correctly. */
    #[DataProvider("timesInDifferentDays")]
    public function testIsInSameDayAs2(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isOnSameDayAs($second));
    }

    /** Ensure times in the same hour are detected correctly. */
    #[DataProvider("timesInSameHour")]
    public function testIsInSameHourAs1(int $year, Month $month, int $day, int $hour, int $minute, int $otherMinute): void
    {
        $utc = TimeZone::lookup("UTC");

        // arbitrary times in the same hour
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $minute, 59, 824, $utc)->isInSameHourAs(DateTime::create($year, $month, $day, $hour, $otherMinute, 1, 22, $utc)));
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $otherMinute, 12, 7, $utc)->isInSameHourAs(DateTime::create($year, $month, $day, $hour, $minute, 39, 22, $utc)));
    }

    /** Ensure times in different hours are detected correctly. */
    #[DataProvider("timesInDifferentHours")]
    #[DataProvider("timesInDifferentDays")]
    public function testIsInSameHourAs2(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isInSameHourAs($second));
    }

    /** Ensure times in the same minute are detected correctly. */
    #[DataProvider("timesInSameMinute")]
    public function testIsInSameMinuteAs1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $otherSecond): void
    {
        $utc = TimeZone::lookup("UTC");

        // arbitrary times in the same minute
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $minute, $second, 296, $utc)->isInSameMinuteAs(DateTime::create($year, $month, $day, $hour, $minute, $otherSecond, 381, $utc)));
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $minute, $otherSecond, 989, $utc)->isInSameMinuteAs(DateTime::create($year, $month, $day, $hour, $minute, $second, 200, $utc)));
    }

    /** Ensure times in different minutes are detected correctly. */
    #[DataProvider("timesInDifferentMinutes")]
    #[DataProvider("timesInDifferentHours")]
    #[DataProvider("timesInDifferentDays")]
    public function testIsInSameMinuteAs2(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isInSameMinuteAs($second));
    }

    /** Ensure times in the same second are detected correctly. */
    #[DataProvider("timesInSameSecond")]
    public function testIsInSameSecondAs1(int $year, Month $month, int $day, int $hour, int $minute, int $second, int $millisecond, int $otherMillisecond): void
    {
        $utc = TimeZone::lookup("UTC");

        // arbitrary times in the same second
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $utc)->isInSameSecondAs(DateTime::create($year, $month, $day, $hour, $minute, $second, $otherMillisecond, $utc)));
        self::assertTrue(DateTime::create($year, $month, $day, $hour, $minute, $second, $otherMillisecond, $utc)->isInSameSecondAs(DateTime::create($year, $month, $day, $hour, $minute, $second, $millisecond, $utc)));
    }

    /** Ensure times in different seconds are detected correctly. */
    #[DataProvider("timesInDifferentSeconds")]
    #[DataProvider("timesInDifferentMinutes")]
    #[DataProvider("timesInDifferentHours")]
    #[DataProvider("timesInDifferentDays")]
    public function testIsInSameSecondAs2(DateTime $first, DateTime $second): void
    {
        self::assertFalse($first->isInSameSecondAs($second));
    }

    /** A selection of DateTime objects and the expected number of ms they are before the epoch. */
    public static function dateTimesAndMillisecondsBeforeEpoch(): iterable
    {
        yield "1ms-before-epoch" => [DateTime::create(1969, Month::December, 31,  23, 59, 59, 999), 1];
        yield "1s-before-epoch" => [DateTime::create(1969, Month::December, 31,  23, 59, 59), 1000];
        yield "leap-year-before-epoch" => [DateTime::create(1968, Month::February, 28,  23, 59, 59), 58060801000];
        yield "several-leap-years-before-epoch" => [DateTime::create(1959, Month::September, 11,  18, 32, 15), 325229265000];
    }

    /** Ensure we calculate the ms before the epoch correctly. */
    #[DataProvider("dateTimesAndMillisecondsBeforeEpoch")]
    public function testMillisecondsBeforeEpoch1(DateTime $testDateTime, int $expected): void
    {
        self::assertSame($expected, (new XRay($testDateTime))->millisecondsBeforeEpoch());
    }

    /** Ensure millisecondsBeforeEpoch() asserts the DateTime is before the epoch. */
    public function testMillisecondsBeforeEpoch2(): void
    {
        if (!$this->phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Expected DateTime to be before the epoch");

        (new XRay(DateTime::create(1970, Month::January, 1)))->millisecondsBeforeEpoch();
    }

    // TODO millisecondsAfterEpoch()
    // TODO syncGregorianDecrementHour()
    // TODO syncGregorianIncrementHour()
    // TODO syncGregorianPostEpoch()
    // TODO syncGregorianPreEpoch()
    // TODO syncGregorian()
}
