<?php

namespace MeridiemTests;

use Meridiem\UtcOffset;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

use function Meridiem\first;
use function sprintf;

class UtcOffsetTest extends TestCase
{

    public static function positiveHours(): iterable
    {
        for ($hours = 1; $hours < 24; ++$hours) {
            yield "positive-hours-{$hours}" => [$hours];
        }
    }

    public static function negativeHours(): iterable
    {
        for ($hours = -1; $hours > -24; --$hours) {
            yield "negative-hours-{$hours}" => [$hours];
        }
    }

    public static function zeroHours(): iterable
    {
        yield "zero-hours" => [0];
    }

    public static function validPositiveMinutes(): iterable
    {
        for ($minutes = 0; $minutes < 60; ++$minutes) {
            yield "positive-minutes-{$minutes}" => [$minutes];
        }
    }

    public static function validNegativeMinutes(): iterable
    {
        for ($minutes = 0; $minutes > -60; --$minutes) {
            yield "negative-minutes-{$minutes}" => [$minutes];
        }
    }

    public static function invalidPositiveMinutes(): iterable
    {
        for ($minutes = 60; $minutes < 100; ++$minutes) {
            yield "invalid-positive-minutes-{$minutes}" => [$minutes];
        }

        yield "int-max-minutes" => [PHP_INT_MAX];
    }

    public static function invalidNegativeMinutes(): iterable
    {
        for ($minutes = -60; $minutes > -100; --$minutes) {
            yield "invalid-negative-minutes-{$minutes}" => [$minutes];
        }

        yield "int-min-minutes" => [PHP_INT_MIN];
    }

    public static function positiveOffsets(): iterable
    {
        foreach (self::validPositiveMinutes() as $minute) {
            yield "offset-0-{$minute}" => [0, first($minute),];
        }

        foreach (self::positiveHours() as $hour) {
            $hour = first($hour);

            foreach (self::validPositiveMinutes() as $minute) {
                $minute = first($minute);
                yield "offset-{$hour}-{$minute}" => [$hour, $minute,];
            }
        }
    }

    public static function negativeOffsets(): iterable
    {
        foreach (self::negativeHours() as $hour) {
            $hour = first($hour);

            foreach (self::validNegativeMinutes() as $minute) {
                $minute = first($minute);
                yield "offset-{$hour}-{$minute}" => [$hour, $minute,];
            }
        }
    }

    public static function positiveOffsetStrings(): iterable
    {
        foreach (self::positiveOffsets() as [$hours, $minutes]) {
            yield "positive-offset-string-{$hours}-{$minutes}"  => [sprintf("+%02d%02d", $hours, $minutes), $hours, $minutes];
            yield "positive-offset-string-{$hours}-{$minutes}"  => [sprintf("+%02d:%02d", $hours, $minutes), $hours, $minutes];
        }
    }

    public static function negativeOffsetStrings(): iterable
    {
        foreach (self::negativeOffsets() as [$hours, $minutes]) {
            yield "negative-offset-string-{$hours}-{$minutes}"  => [sprintf("-%02d%02d", $hours, $minutes), abs($hours), abs($minutes)];
            yield "negative-offset-string-{$hours}-{$minutes}"  => [sprintf("-%02d:%02d", $hours, $minutes), abs($hours), abs($minutes)];
        }
    }

    /** Ensure we can construct with a positive number of hours. */
    #[DataProvider("positiveHours")]
    #[DataProvider("zeroHours")]
    public function testConstructor1(int $hours): void
    {
        $actual = new UtcOffset($hours, 0);
        self::assertSame($hours, $actual->hours());
        self::assertSame(0, $actual->minutes());

        $actual = new UtcOffset($hours, 30);
        self::assertSame($hours, $actual->hours());
        self::assertSame(30, $actual->minutes());
    }

    /** Ensure we can construct with a negative number of hours. */
    #[DataProvider("negativeHours")]
    #[DataProvider("zeroHours")]
    public function testConstructor2(int $hours): void
    {
        $actual = new UtcOffset($hours, 0);
        self::assertSame($hours, $actual->hours());
        self::assertSame(0, $actual->minutes());

        $actual = new UtcOffset($hours, -30);
        self::assertSame($hours, $actual->hours());
        self::assertSame(-30, $actual->minutes());
    }

    /** Ensure we can't construct with a positive number of hours and a negative number of minutes. */
    #[DataProvider("positiveHours")]
    public function testConstructor3(int $hours): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected hours and minutes with compatible signs, found {$hours} and -30");
        new UtcOffset($hours, -30);
    }

    /** Ensure we can't construct with a negative number of hours and a positive number of minutes. */
    #[DataProvider("negativeHours")]
    public function testConstructor4(int $hours): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected hours and minutes with compatible signs, found {$hours} and 30");
        new UtcOffset($hours, 30);
    }
    
    /** Ensure we can construct with a valid positive number of minutes. */
    #[DataProvider("validPositiveMinutes")]
    public function testConstructor5(int $minutes): void
    {
        $actual = new UtcOffset(0, $minutes);
        self::assertSame(0, $actual->hours());
        self::assertSame($minutes, $actual->minutes());

        $actual = new UtcOffset(5, $minutes);
        self::assertSame(5, $actual->hours());
        self::assertSame($minutes, $actual->minutes());
    }

    /** Ensure we can construct with a valid negative number of minutes. */
    #[DataProvider("validNegativeMinutes")]
    public function testConstructor6(int $minutes): void
    {
        $actual = new UtcOffset(0, $minutes);
        self::assertSame(0, $actual->hours());
        self::assertSame($minutes, $actual->minutes());

        $actual = new UtcOffset(-8, $minutes);
        self::assertSame(-8, $actual->hours());
        self::assertSame($minutes, $actual->minutes());
    }

    /** Ensure we can't construct with an invalid positive number of minutes. */
    #[DataProvider("invalidPositiveMinutes")]
    public function testConstructor7(int $minutes): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected minutes between -59 and 59 inclusive, found {$minutes}");
        new UtcOffset(3, $minutes);
    }

    /** Ensure we can't construct with an invalid negative number of minutes. */
    #[DataProvider("invalidNegativeMinutes")]
    public function testConstructor8(int $minutes): void
    {
        if (!self::phpAssertionsActive()) {
            self::markTestSkipped("PHP assertions must be active for this test");
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected minutes between -59 and 59 inclusive, found {$minutes}");
        new UtcOffset(-11, $minutes);
    }

    /** Ensure we can get the offset string for positive offsets. */
    #[DataProvider("positiveOffsets")]
    public function testOffset1(int $hours, int $minutes): void
    {
        $actual = new UtcOffset($hours, $minutes);
        self::assertSame(sprintf("+%02d%02d", $hours, $minutes), $actual->offset());
        self::assertSame(sprintf("+%02d%02d", $hours, $minutes), $actual->offset(false));
        self::assertSame(sprintf("+%02d:%02d", $hours, $minutes), $actual->offset(true));
    }

    /** Ensure we can get the offset string for negative offsets. */
    #[DataProvider("negativeOffsets")]
    public function testOffset2(int $hours, int $minutes): void
    {
        $actual = new UtcOffset($hours, $minutes);
        self::assertSame(sprintf("-%02d%02d", abs($hours), abs($minutes)), $actual->offset());
        self::assertSame(sprintf("-%02d%02d", abs($hours), abs($minutes)), $actual->offset(false));
        self::assertSame(sprintf("-%02d:%02d", abs($hours), abs($minutes)), $actual->offset(true));
    }
    
    /** Ensure we get the correct number of seconds in the offset. */
    #[DataProvider("positiveOffsets")]
    #[DataProvider("negativeOffsets")]
    public function testOffsetSeconds1(int $hours, int $minutes): void
    {
        $actual = new UtcOffset($hours, $minutes);
        self::assertSame(($hours * 60 * 60) + ($minutes * 60), $actual->offsetSeconds());
    }
    
    /** Ensure we get the correct number of milliseconds in the offset. */
    #[DataProvider("positiveOffsets")]
    #[DataProvider("negativeOffsets")]
    public function testOffsetMilliseconds1(int $hours, int $minutes): void
    {
        $actual = new UtcOffset($hours, $minutes);
        self::assertSame(($hours * 60 * 60 * 1000) + ($minutes * 60 * 1000), $actual->offsetMilliseconds());
    }

    /** Ensure we can successfully parse positive offset strings. */
    #[DataProvider("positiveOffsetStrings")]
    public function testParse1(string $offset, int $expectedHours, int $expectedMinutes): void
    {
        $actual = UtcOffset::parse($offset);
        self::assertSame($expectedHours, $actual->hours());
        self::assertSame($expectedMinutes, $actual->minutes());
    }

    /** Ensure we can successfully parse negative offset strings. */
    #[DataProvider("negativeOffsetStrings")]
    public function testParse2(string $offset, int $expectedHours, int $expectedMinutes): void
    {
        $actual = UtcOffset::parse($offset);
        self::assertSame($expectedHours, $actual->hours());
        self::assertSame($expectedMinutes, $actual->minutes());
    }

    public static function invalidOffsetStrings(): iterable
    {
        yield "empty" => [""];
        yield "whitespace" => ["  "];
        yield "leading-whitespace" => [" +0100"];
        yield "leading-whitespace-with-colon" => [" +01:00"];
        yield "leading-whitespace-negative" => [" -0100"];
        yield "leading-whitespace-negative-with-colon" => [" -01:00"];
        yield "trailing-whitespace" => ["+0100 "];
        yield "trailing-whitespace-with-colon" => ["+01:00 "];
        yield "trailing-whitespace-negative" => ["-0100 "];
        yield "trailing-whitespace-negative-with-colon" => ["-01:00 "];
        yield "surrounding-whitespace" => [" +0100 "];
        yield "surrounding-whitespace-with-colon" => [" +01:00 "];
        yield "surrounding-whitespace-negative" => [" -0100 "];
        yield "surrounding-whitespace-negative-with-colon" => [" -01:00 "];
        yield "excess-signs-positive" => ["+09:+30"];
        yield "excess-signs-positive-negative" => ["+10:-15"];
        yield "excess-signs-negative-positive" => ["-06:+08"];
        yield "excess-signs-negative" => ["-11:-45"];
        yield "truncated" => ["+100"];
        yield "truncated-negative" => ["-100"];
        yield "truncated-hour" => ["+1:00"];
        yield "truncated-negative-hour" => ["-1:00"];
        yield "truncated-minute" => ["+11:2"];
        yield "truncated-negative-minute" => ["-05:3"];
        yield "truncated-hour-and-minute" => ["+4:3"];
        yield "truncated-negative-hour-and-minute" => ["-7:1"];
        yield "wrong-delimiter-space" => ["+01 00"];
        yield "wrong-delimiter-dash" => ["+01-00"];
        yield "wrong-delimiter-underscore" => ["+01_00"];
        yield "wrong-delimiter-slash" => ["+01/00"];
        yield "wrong-delimiter-period" => ["+01.00"];
        yield "wrong-delimiter-space-negative" => ["-01 00"];
        yield "wrong-delimiter-dash-negative" => ["-01-00"];
        yield "wrong-delimiter-underscore-negative" => ["-01_00"];
        yield "wrong-delimiter-slash-negative" => ["-01/00"];
        yield "wrong-delimiter-period-negative" => ["-01.00"];
    }

    /** Ensure invalid offset strings are rejected. */
    #[DataProvider("invalidOffsetStrings")]
    public function testParse3(string $offset): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Expected valid timezone offset, found \"{$offset}\"");
        UtcOffset::parse($offset);
    }
}
