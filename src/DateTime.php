<?php

namespace Meridiem;

use DateTime as PhpDateTime;
use DateTimeInterface as PhpDateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\Contracts\DateTimeComparison as DateTimeComparisonContract;

/**
 * Representation of a point in time in the Gregorian calendar.
 *
 * Points in time are precise to 1ms. The DateTimeComparison contract is implemented to enable dates to be compared.
 * Objects are immutable.
 *
 * TODO handle Gregorian calendar transition.
 */
class DateTime implements DateTimeContract, DateTimeComparisonContract
{
    public const string DefaultTimeZone = "UTC";

    private const int MinYear = -9999;

    private const int MaxYear = 9999;

    private const int MinDay = 1;
    
    private const int MinHour = 0;

    private const int MaxHour = 23;

    private const int MinMinute = 0;

    private const int MaxMinute = 59;

    private const int MinSecond = 0;

    private const int MaxSecond = 59;

    private const int MinMillisecond = 0;

    private const int MaxMillisecond = 999;

    private const int EpochYear = 1970;

    private const Month EpochMonth = Month::January;

    private const int EpochDay = 1;

    private const Weekday EpochWeekday = Weekday::Thursday;

    private const int EpochHour = 0;

    private const int EpochMinute = 0;

    private const int EpochSecond = 0;

    private const int EpochMillisecond = 0;

    // How many ms in other units
    private const int MillisecondsPerSecond = 1000;
    
    private const int MillisecondsPerMinute = self::MillisecondsPerSecond * 60;

    private const int MillisecondsPerHour = self::MillisecondsPerMinute * 60;

    private const int MillisecondsPerDay = self::MillisecondsPerHour * 24;

    private const int MillisecondsPerYear = self::MillisecondsPerDay * 365;

    // Unix timestamp is clean
    private const int CleanUnix = 0x01;

    // Gregorian date-time is clean
    private const int CleanGregorian = 0x02;

    // Both are clean
    private const int CleanBoth = 0x03;

    private int $unixMs;

    private int $year;

    private Month $month;

    private int $day;

    private int $hour;

    private int $minute;

    private int $second;

    private int $millisecond;

    private DateTimeZone $timeZone;

    // What state is currently clean - always one of the clean constants Unix/Gregorian/Both
    private int $clean;

    protected function __construct(int $yearOrUnixMs, ?Month $month = null, ?int $day = null, ?int $hour = null, ?int $minute = null, ?int $second = null, ?int $millisecond = 0, ?DateTimeZone $timeZone = null)
    {
        if (null === $month) {
            $this->unixMs = $yearOrUnixMs;
            $this->year = 0;
            $this->month = Month::January;
            $this->day = 0;
            $this->hour = 0;
            $this->minute = 0;
            $this->second = 0;
            $this->millisecond = 0;
            $this->timeZone = new DateTimeZone(self::DefaultTimeZone);
            $this->clean = self::CleanUnix;
        } else {
            assert(self::MinYear <= $yearOrUnixMs && self::MaxYear >= $yearOrUnixMs, new InvalidArgumentException("Expected year between -9999 and 9999 inclusive, found {$yearOrUnixMs}"));
            assert(self::MinDay <= $day && $month->dayCount($yearOrUnixMs) >= $day, new InvalidArgumentException("Expected day between 1 and {$month->dayCount($yearOrUnixMs)} inclusive, found {$day}"));
            assert(self::MinHour <= $hour && self::MaxHour >= $hour, new InvalidArgumentException("Expected hour between 0 and 23 inclusive, found {$hour}"));
            assert(self::MinMinute <= $minute && self::MaxMinute >= $minute, new InvalidArgumentException("Expected minute between 0 and 59 inclusive, found {$minute}"));
            assert(self::MinSecond <= $second && self::MaxSecond >= $second, new InvalidArgumentException("Expected second between 0 and 59 inclusive, found {$second}"));
            assert(self::MinMillisecond <= $millisecond && self::MaxMillisecond >= $millisecond, new InvalidArgumentException("Expected millisecond between 0 and 999 inclusive, found {$millisecond}"));

            $this->year = $yearOrUnixMs;
            $this->month = $month;
            $this->day = $day;
            $this->hour = $hour ?? 0;
            $this->minute = $minute ?? 0;
            $this->second = $second ?? 0;
            $this->millisecond = $millisecond ?? 0;
            $this->timeZone = ($timeZone ? clone $timeZone : new DateTimeZone(self::DefaultTimeZone));
            $this->unixMs = 0;
            $this->clean = self::CleanGregorian;
        }
    }

    public function __clone()
    {
        $this->timeZone = clone $this->timeZone;
    }

    /**
     * Compare two dates on their gregorian calendar properties.
     *
     * @return int < 0 if the first is earlier, > 0 if the second is earlier, or 0 if they're the same date-time.
     */
    protected function compareGregorian(DateTimeContract $other): int
    {
        return $this->year <=> $other->year()
            ?: $this->month->value <=> $other->month()->value
            ?: $this->day <=> $other->day()
            ?: $this->hour <=> $other->hour()
            ?: $this->minute <=> $other->minute()
            ?: $this->second <=> $other->second()
            ?: $this->millisecond <=> $other->millisecond();
    }

    protected function compareUnix(self $other): int
    {
        return $this->unixMs <=> $other->unixTimestampMs();
    }

    /** Helper to check whether a year is a leap year. */
    protected static function isLeapYear(int $year): bool
    {
        return $year % 400 === 0 && ($year % 100 !== 0 || $year % 400 === 0);
    }

    /**
     * Calculates the number of milliseconds between two dates.
     *
     * This is used internally to re-calculate the Unix timestamp when it's required but it not currently clean. The
     * result is negative if the first date its before the second date.
     *
     * The caller asserts that both DateTime objects have clean Gregorian representations.
     */
    protected static function millisecondsBetween(self $first, self $second): int
    {
        if (0 > self::compareGregorian($first, $second)) {
            return -self::millisecondsBetween($second, $first);
        }

        $milliseconds = ($second->year - $first->year) * self::MillisecondsPerYear;

        for ($month = 1; $month < $second->month->value; ++$month) {
            $milliseconds += self::MillisecondsPerDay * Month::from($month)->dayCount($second->year);
        }

        $milliseconds += ($second->day - 1) * self::MillisecondsPerDay;
        $milliseconds += $second->hour * self::MillisecondsPerHour;
        $milliseconds += $second->minute * self::MillisecondsPerMinute;
        $milliseconds += $second->second * self::MillisecondsPerSecond;
        $milliseconds += $second->millisecond;

        // add a day for each leap year between first and last year (including first but not including last)
        for ($year = $first->year; $year < $second->year; ++$year) {
            if (self::isLeapYear($year)) {
                $milliseconds += self::MillisecondsPerDay;
            }
        }

        // if the last year is a leap year, and it's after Feb 29th, add another day
        if (self::isLeapYear($second->year) && $second->month->value > 2) {
            $milliseconds += self::MillisecondsPerDay;
        }

        // adjust for the relative timezones
//        $offset = self::MillisecondsPerHour((int) substr($first->timeZone->getName()))
        return $milliseconds;
    }

    /** Helper to check whether the unix timestamp is currently clean. */
    protected final function isUnixClean(): bool
    {
        return 0 !== $this->clean & self::CleanUnix;
    }

    /** Helper to check whether the Gregorian calendar properties are currently clean. */
    protected final function isGregorianClean(): bool
    {
        return 0 !== $this->clean & self::CleanGregorian;
    }

    /** Helper to bring the Unix timestamp into sync with the Gregorian calendar properties. */
    protected final function syncUnix(): void
    {
        static $epoch = new self(self::EpochYear, self::EpochMonth, self::EpochDay, self::EpochHour, self::EpochMinute, self::EpochSecond, self::EpochMillisecond);
        $this->unixMs = self::millisecondsBetween($epoch, $this);
        $this->clean = self::CleanBoth;
    }

    /** Helper to bring the Gregorian calendar properties into sync with the Unix timestamp. */
    protected final function syncGregorian(): void
    {
        $dateTime = PhpDateTime::createFromFormat("U", (string) (int) floor($this->unixMs / self::MillisecondsPerSecond), $this->timeZone);
        $this->year = (int) $dateTime->format("Y");
        $this->month = Month::from((int) $dateTime->format("n"));
        $this->day = (int) $dateTime->format("j");
        $this->hour = (int) $dateTime->format("H");
        $this->minute = (int) $dateTime->format("i");
        $this->second = (int) $dateTime->format("s");
        $this->millisecond = $this->unixMs % self::MillisecondsPerSecond;

        $this->clean = self::CleanBoth;
    }

    /**
     * Create a DateTime object from a valid point in time in the Gregorian calendar.
     *
     * @param int $year The Gregorian year, from -9999 to 9999.
     * @param Month $month The Gregorian month.
     * @param int $day The day of the Gregorian month. Must be a valid day for the year and month.
     * @param int $hour The hour (24h, 0-23))
     * @param int $minute The minute (0-59)
     * @param int $second The second (0-59)
     * @param int $millisecond The millisecond (0-999)
     * @param DateTimeZone $timeZone The timezone in which the date-time is expressed.
     *
     * @return self
     */
    public static function create(int $year, Month $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, int $millisecond = 0, DateTimeZone $timeZone = new DateTimeZone(self::DefaultTimeZone)): self
    {
        return new self($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone);
    }

    /** Create a DateTime object from a PHP DateTime or DateTimeImmutable object. */
    public static function fromDateTime(PhpDateTimeInterface $dateTime): self
    {
        return new self(($dateTime->getTimestamp() * self::MillisecondsPerSecond) + (int) $dateTime->format("v"));
    }

    /**
     * Create a new DateTime object from a Unix timestamp (in seconds).
     *
     * The created DateTime will have 0 milliseconds, and will have the UTC timezone.
     *
     * @param int $unixTimestamp The Unix timestamp (seconds since 00:00:00 on 1st Jan, 1970).
     */
    public static function fromUnixTimestamp(int $unixTimestamp): self
    {
        return new self($unixTimestamp * self::MillisecondsPerSecond);
    }

    /**
     * Create a new DateTime object from a Unix timestamp (in milliseconds).
     *
     * The created DateTime will have the UTC timezone.
     *
     * @param int $unixTimestampMs The Unix timestamp (milliseconds since 00:00:00 on 1st Jan, 1970).
     */
    public static function fromUnixTimestampMs(int $unixTimestampMs): self
    {
        return new self($unixTimestampMs);
    }

    /**
     * Create a DateTime representing the current time.
     *
     * The DateTime will have the UTC timezone.
     */
    public static function now(): self
    {
        return new self((int) (microtime(true) * self::MillisecondsPerSecond));
    }

    /** Create a new DateTime with the time from the current DateTime and a specified date. */
    public function withDate(int $day, Month $month, int $year): static
    {
        assert(self::MinYear <= $year && self::MaxYear >= $year, new InvalidArgumentException("Expected year between -9999 and 9999 inclusive, found {$year}"));
        assert(self::MinDay <= $day && $month->dayCount($year) >= $day, new InvalidArgumentException("Expected day between 1 and {$month->dayCount($year)} inclusive, found {$day}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->year = $year;
        $clone->month = $month;
        $clone->day = $day;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    /** Fetch the year of the point in time. */
    public function year(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->year;
    }

    /** Fetch the month of the point in time. */
    public function month(): Month
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->month;
    }

    /** Fetch the day of the point in time. */
    public function day(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->day;
    }

    public function weekday(): Weekday
    {
        $daysSinceEpoch = (int) floor($this->unixTimestampMs() / self::MillisecondsPerDay);
        $weekday = (self::EpochWeekday->value + $daysSinceEpoch) % 7;
        return Weekday::from($weekday);
    }

    /**
     * Create a new DateTime with the date and timezone of the current DateTime and a specified time.
     *
     * The time given is in the timezone of the DateTime.
     *
     * @param int $hour The hour of the new time (0-23).
     * @param int $minute The hour of the new time (0-59).
     * @param int $second The second of the new time (0-59). Default is 0.
     * @param int $millisecond The millisecond of the new time (0-999). Default is 0.
     *
     * @return self The newly-created DateTime.
     */
    public function withTime(int $hour, int $minute, int $second = 0, int $millisecond = 0): static
    {
        assert(self::MinHour <= $hour && self::MaxHour >= $hour, new InvalidArgumentException("Expected hour between 0 and 23 inclusive, found {$hour}"));
        assert(self::MinMinute <= $minute && self::MaxMinute >= $minute, new InvalidArgumentException("Expected minute between 0 and 59 inclusive, found {$minute}"));
        assert(self::MinSecond <= $second && self::MaxSecond >= $second, new InvalidArgumentException("Expected second between 0 and 59 inclusive, found {$second}"));
        assert(self::MinMillisecond <= $millisecond && self::MaxMillisecond >= $millisecond, new InvalidArgumentException("Expected millisecond between 0 and 999 inclusive, found {$millisecond}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->hour = $hour;
        $clone->minute = $minute;
        $clone->second = $second;
        $clone->millisecond = $millisecond;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    /** Create a new DateTime with the date and time from the current DateTime, but with a specified hour. */
    public function withHour(int $hour): static
    {
        assert(self::MinHour <= $hour && self::MaxHour >= $hour, new InvalidArgumentException("Expected hour between 0 and 23 inclusive, found {$hour}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->hour = $hour;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    public function hour(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->hour;
    }

    /** Create a new DateTime with the date and time from the current DateTime, but with a specified minute. */
    public function withMinute(int $minute): static
    {
        assert(self::MinMinute <= $minute && self::MaxMinute >= $minute, new InvalidArgumentException("Expected minute between 0 and 59 inclusive, found {$minute}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->minute = $minute;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    public function minute(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->minute;
    }

    /** Create a new DateTime with the date and time from the current DateTime, but with a specified second. */
    public function withSecond(int $second): static
    {
        assert(self::MinSecond <= $second && self::MaxSecond >= $second, new InvalidArgumentException("Expected second between 0 and 59 inclusive, found {$second}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->second = $second;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    public function second(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->second;
    }

    /** Create a new DateTime with the date and time from the current DateTime, but with a specified millisecond. */
    public function withMillisecond(int $millisecond): static
    {
        assert(self::MinMillisecond <= $millisecond && self::MaxMillisecond >= $millisecond, new InvalidArgumentException("Expected millisecond between 0 and 999 inclusive, found {$millisecond}"));

        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->millisecond = $millisecond;
        $this->clean = self::CleanGregorian;
        return $clone;
    }

    public function millisecond(): int
    {
        if (!$this->isGregorianClean()) {
            $this->syncGregorian();
        }

        return $this->millisecond;
    }

    /** Fetch the Unix timestamp (in seconds) for the point in time. */
    public function unixTimestamp(): int
    {
        return (int) floor($this->unixTimestampMs() / self::MillisecondsPerSecond);
    }

    /** Fetch the Unix timestamp (in milliseconds) for the point in time. */
    public function unixTimestampMs(): int
    {
        if (!$this->isUnixClean()) {
            $this->syncUnix();
        }

        return $this->unixMs;
    }

    /**
     * Create a new DateTime that is a duplicate of the current DateTime, adjusted for a new time zone.
     *
     * The new DateTime represents the same point in time, with its Gregorian calendar representation set to that of
     * provided time zone. For example, if a DateTime represents 12:47 with a timezone of UTC, calling
     * withTimeZone(new DateTimeZone("+0100")) on it will create a new DateTime that represents 13:47 +0100 (which is
     * the same point in time as 12:47 UTC).
     *
     * @return DateTime the DateTime with the new timezone.
     */
    public function withTimeZone(DateTimeZone $timeZone): static
    {
        // given we've got to sync, might as well sync before cloning as we get both synced for the price of one
        if (!$this->isUnixClean()) {
            $this->syncUnix();
        }

        $clone = clone $this;
        $clone->timeZone = $timeZone;
        $clone->clean = self::CleanUnix;
        return $clone;
    }

    /** Fetch the timezone for the point in time. */
    public function timeZone(): DateTimeZone
    {
        return $this->timeZone;
    }

    public function isBefore(DateTimeContract $other): bool
    {
        if ($this->isUnixClean()) {
            return 0 > $this->compareUnix($other);
        }

        return 0 > $this->compareGregorian($other);
    }

    public function isAfter(DateTimeContract $other): bool
    {
        if ($this->isUnixClean()) {
            return 0 < $this->compareUnix($other);
        }

        return 0 < $this->compareGregorian($other);
    }

    public function isEqualTo(DateTimeContract $other): bool
    {
        if ($this->isUnixClean()) {
            return 0 === $this->compareUnix($other);
        }

        return 0 === $this->compareGregorian($other);
    }

    public function isInSameYearAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year();
    }

    public function isInSameMonthAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year()
            && $this->month() === $other->month();
    }

    public function isOnSameDayAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year()
            && $this->month() === $other->month()
            && $this->day() === $other->day();
    }

    public function isInSameHourAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year()
            && $this->month() === $other->month()
            && $this->day() === $other->day()
            && $this->hour() === $other->hour();
    }

    public function isInSameMinuteAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year()
            && $this->month() === $other->month()
            && $this->day() === $other->day()
            && $this->hour() === $other->hour()
            && $this->minute() === $other->minute();
    }

    public function isInSameSecondAs(DateTimeContract $other): bool
    {
        return $this->year() === $other->year()
            && $this->month() === $other->month()
            && $this->day() === $other->day()
            && $this->hour() === $other->hour()
            && $this->minute() === $other->minute()
            && $this->second() === $other->second();
    }
}
