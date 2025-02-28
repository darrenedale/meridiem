<?php

namespace Meridiem;

use DateTimeInterface as PhpDateTimeInterface;
use InvalidArgumentException;
use LogicException;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\Contracts\DateTimeComparison as DateTimeComparisonContract;
use Meridiem\Contracts\TimeZone as TimeZoneContract;

/**
 * Representation of a point in time in the Gregorian calendar.
 *
 * Points in time are precise to 1ms. The DateTimeComparison contract is implemented to enable dates to be compared.
 * Objects are immutable.
 *
 * TODO test DST adjustment for timezones.
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

    // When Unix timestamp is clean
    private const int CleanUnix = 0x01;

    // When Gregorian date-time is clean
    private const int CleanGregorian = 0x02;

    private int $unixMs;

    private int $year;

    private Month $month;

    private int $day;

    private int $hour;

    private int $minute;

    private int $second;

    private int $millisecond;

    private TimeZoneContract $timeZone;

    // What state is currently clean - always one of the clean constants Unix/Gregorian/Both
    private int $clean;

    protected function __construct(int $yearOrUnixMs, ?Month $month = null, ?int $day = null, ?int $hour = null, ?int $minute = null, ?int $second = null, ?int $millisecond = 0, ?TimeZoneContract $timeZone = null)
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
            $this->timeZone = TimeZone::lookup(self::DefaultTimeZone);
            $this->clean = self::CleanUnix;
        } else {
            // TODO these should be runtime exceptions
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
            $this->timeZone = ($timeZone ? clone $timeZone : TimeZone::lookup(self::DefaultTimeZone));
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
        return ($this->year <=> $other->year())
            ?: ($this->month->value <=> $other->month()->value)
            ?: ($this->day <=> $other->day())
            ?: ($this->hour <=> $other->hour())
            ?: ($this->minute <=> $other->minute())
            ?: ($this->second <=> $other->second())
            ?: ($this->millisecond <=> $other->millisecond());
    }

    protected function compareUnix(DateTimeContract $other): int
    {
        return $this->unixMs <=> $other->unixTimestampMs();
    }

    /** Helper to check whether a year is a leap year. */
    protected static function isLeapYear(int $year): bool
    {
        return ($year % 4) === 0 && (($year % 100) !== 0 || ($year % 400) === 0);
    }

    /** Helper to check whether the unix timestamp is currently clean. */
    protected final function isUnixClean(): bool
    {
        return 0 !== ($this->clean & self::CleanUnix);
    }

    /** Helper to check whether the Gregorian calendar properties are currently clean. */
    protected final function isGregorianClean(): bool
    {
        return 0 !== ($this->clean & self::CleanGregorian);
    }

    /** Helper for syncUnix() to calculate how many milliseconds a Gregorian date-time is before the epoch. */
    protected final function millisecondsBeforeEpoch(): int
    {
        $epoch = UnixEpoch::dateTime();
        assert($this->isGregorianClean(), new LogicException("Expected DateTime to be Gregorian clean"));
        assert(0 < $epoch->compareGregorian($this));

        $milliseconds = ($epoch->year - 1 - $this->year) * GregorianRatios::MillisecondsPerYear;

        for ($month = $this->month->value + 1; $month <= Month::December->value; ++$month) {
            $milliseconds += GregorianRatios::MillisecondsPerDay * Month::from($month)->dayCount($this->year);
        }

        $milliseconds += GregorianRatios::MillisecondsPerDay * ($this->month->dayCount($this->year) - $this->day);
        $milliseconds += GregorianRatios::MillisecondsPerHour * (self::MaxHour - $this->hour);
        $milliseconds += GregorianRatios::MillisecondsPerMinute * (self::MaxMinute - $this->minute);
        $milliseconds += GregorianRatios::MillisecondsPerSecond * (self::MaxSecond - $this->second);
        $milliseconds += GregorianRatios::MillisecondsPerSecond - $this->millisecond;

        // add a day for each leap year between the dates
        for ($year = $epoch->year - 1; $year > $this->year; --$year) {
            if (self::isLeapYear($year)) {
                $milliseconds += GregorianRatios::MillisecondsPerDay;
            }
        }
        
        // and the to year, if the date is before 29th Feb
        if (self::isLeapYear($this->year) && ($this->month->isBefore(Month::February) || (Month::February === $this->month && $this->day < 29))) {
            $milliseconds += GregorianRatios::MillisecondsPerDay;
        }

        return $milliseconds - $this->timeZone->offset($this)->offsetMilliseconds();
    }

    /** Helper for syncUnix() to calculate how many milliseconds a Gregorian date-time is after the epoch. */
    protected final function millisecondsAfterEpoch(): int
    {
        $epoch = UnixEpoch::dateTime();

        // whole years since the epoch
        $milliseconds = ($this->year - $epoch->year) * GregorianRatios::MillisecondsPerYear;

        // leap days in whole years that are leap years
        for ($year = $epoch->year; $year < $this->year; ++$year) {
            if (self::isLeapYear($year)) {
                $milliseconds += GregorianRatios::MillisecondsPerDay;
            }
        }

        // whole months this year (includes the extra leap year day if applicable)
        $month = $epoch->month;

        while ($month->isBefore($this->month)) {
            $milliseconds += GregorianRatios::MillisecondsPerDay * $month->dayCount($this->year);
            $month = $month->next();
        }

        return $milliseconds +
            ($this->day - 1) * GregorianRatios::MillisecondsPerDay
            + $this->hour * GregorianRatios::MillisecondsPerHour
            + $this->minute * GregorianRatios::MillisecondsPerMinute
            + $this->second * GregorianRatios::MillisecondsPerSecond
            + $this->millisecond
            - $this->timeZone->offset($this)->offsetMilliseconds();
    }

    /** Helper to bring the Unix timestamp into sync with the Gregorian calendar properties. */
    protected final function syncUnix(): void
    {
        if (0 < UnixEpoch::dateTime()->compareGregorian($this)) {
            $this->unixMs = -$this->millisecondsBeforeEpoch();
        } else {
            $this->unixMs = $this->millisecondsAfterEpoch();
        }

        $this->clean |= self::CleanUnix;
    }

    /** Helper for syncGregorian() to safely decrement the hour if the timezone DST adjustment requires it. */
    private function syncGregorianDecrementHour(): void
    {
        --$this->hour;

        if (0 > $this->hour) {
            --$this->day;

            if (1 > $this->day) {
                $this->month = $this->month->previous();

                if (Month::December === $this->month) {
                    --$this->year;
                }

                $this->day = $this->month->dayCount($this->year);
            }

            $this->hour += 24;
        }
    }

    /** Helper for syncGregorian() to safely increment the hour if the timezone DST adjustment requires it. */
    private function syncGregorianIncrementHour(): void
    {
        ++$this->hour;

        if (self::MaxHour < $this->hour) {
            $this->hour -= 24;
            ++$this->day;

            if ($this->month->dayCount($this->year) < $this->day) {
                $this->day = 1;
                $this->month = $this->month->next();

                if (Month::January === $this->month) {
                    ++$this->year;
                }
            }
        }
    }

    /**
     * Helper for syncGregorian() to sync to the timestamp when it's after the epoch.
     *
     * @param int $ms The number of ms before the epoch (note this must *always* be a positive value).
     */
    private function syncGregorianPostEpoch(int $ms): void
    {
        $year = UnixEpoch::Year;
        assert(0 < $ms, new LogicException("Expected non-zero number of ms for syncGregorianPositive()"));

        do {
            $yearMs = (self::isLeapYear($year) ? GregorianRatios::MillisecondsPerLeapYear : GregorianRatios::MillisecondsPerYear);

            if ($ms < $yearMs) {
                break;
            }

            ++$year;
            $ms -= $yearMs;
        } while (true);

        foreach (Month::cases() as $month) {
            $monthMs = (GregorianRatios::MillisecondsPerDay * $month->dayCount($year));

            if ($ms < $monthMs) {
                break;
            }

            $ms -= $monthMs;
        }

        $this->year = $year;
        $this->month = $month;
        $this->day = 1 + (int) floor($ms / GregorianRatios::MillisecondsPerDay);
        $ms %= GregorianRatios::MillisecondsPerDay;
        $this->hour = (int) floor($ms / GregorianRatios::MillisecondsPerHour);
        $ms %= GregorianRatios::MillisecondsPerHour;
        $this->minute = (int) floor($ms / GregorianRatios::MillisecondsPerMinute);
        $ms %= GregorianRatios::MillisecondsPerMinute;
        $this->second = (int) floor($ms / GregorianRatios::MillisecondsPerSecond);
        $this->millisecond = $ms % GregorianRatios::MillisecondsPerSecond;
    }

    /**
     * Helper for syncGregorian() to sync to the timestamp when it's before the epoch.
     *
     * @param int $ms The number of ms before the epoch (note this must *always* be a positive value).
     */
    private function syncGregorianPreEpoch(int $ms): void
    {
        assert(0 < $ms, new LogicException("Expected non-zero number of ms for syncGregorianNegative()"));
        $year = UnixEpoch::Year;

        do {
            --$year;
            $yearMs = (self::isLeapYear($year) ? GregorianRatios::MillisecondsPerLeapYear : GregorianRatios::MillisecondsPerYear);

            if ($ms < $yearMs) {
                break;
            }

            $ms -= $yearMs;
        } while (true);

        if (0 === $ms) {
            $this->year = $year + 1;
            $this->month = Month::January;
            $this->day = 1;
            $this->hour = 0;
            $this->minute = 0;
            $this->second = 0;
            $this->millisecond = 0;
            return;
        }

        foreach (array_reverse(Month::cases()) as $month) {
            $monthMs = (GregorianRatios::MillisecondsPerDay * $month->dayCount($year));

            if ($ms < $monthMs) {
                break;
            }

            $ms -= $monthMs;
        }

        $this->year = $year;
        $this->month = $month;
        $this->day = 1 + $month->dayCount($year) - (int) ceil($ms / GregorianRatios::MillisecondsPerDay);
        $ms %= GregorianRatios::MillisecondsPerDay;
        $this->hour = (GregorianRatios::HoursPerDay - (int) ceil($ms / GregorianRatios::MillisecondsPerHour)) % GregorianRatios::HoursPerDay;
        $ms %= GregorianRatios::MillisecondsPerHour;
        $this->minute = (GregorianRatios::MinutesPerHour - (int) ceil($ms / GregorianRatios::MillisecondsPerMinute)) % GregorianRatios::MinutesPerHour;
        $ms %= GregorianRatios::MillisecondsPerMinute;
        $this->second = (GregorianRatios::SecondsPerMinute - (int) ceil($ms / GregorianRatios::MillisecondsPerSecond)) % GregorianRatios::SecondsPerMinute;
        $this->millisecond = (GregorianRatios::MillisecondsPerSecond - ($ms % GregorianRatios::MillisecondsPerSecond)) % GregorianRatios::MillisecondsPerSecond;
    }

    /** Helper to bring the Gregorian calendar properties into sync with the Unix timestamp. */
    protected final function syncGregorian(): void
    {
        // pre-adjust timestamp with timezone's standard UTC offset - we'll do DST adjustment later, if necessary
        $ms = $this->unixMs + $this->timeZone->sdtOffset()->offsetMilliseconds();

        if (0 === $ms) {
            $this->year = UnixEpoch::Year;
            $this->month = UnixEpoch::Month;
            $this->day = UnixEpoch::Day;
            $this->hour = UnixEpoch::Hour;
            $this->minute = UnixEpoch::Minute;
            $this->second = UnixEpoch::Second;
            $this->millisecond = UnixEpoch::Millisecond;
        } else if (0 < $ms) {
            $this->syncGregorianPostEpoch($ms);
        } else if (0 > $ms) {
            $this->syncGregorianPreEpoch(-$ms);
        }

        $this->clean |= self::CleanGregorian;

        // adjust for timezone daylight saving, if applicable
        $transition = $this->timeZone->effectiveTransition($this);

        if ($transition instanceof TimeZoneTransition) {
            // saving minutes are never more than an hour
            // TODO check that the above is true
            $this->minute += $transition->savingMinutes();

            if (0 > $this->minute) {
                $this->minute += 60;
                $this->syncGregorianDecrementHour();
            } else if (self::MaxMinute < $this->minute) {
                $this->minute -= 60;
                $this->syncGregorianIncrementHour();
            }
        }
    }

    /**
     * Create a DateTime object from a valid point in time in the Gregorian calendar.
     *
     * @param int $year The Gregorian year, from -9999 to 9999.
     * @param Month $month The Gregorian month.
     * @param int $day The day of the Gregorian month. Must be a valid day for the year and month.
     * @param int $hour The hour (24h, 0-23). Defaults to 0 (midnight)
     * @param int $minute The minute (0-59). Defaults to 0 (midnight)
     * @param int $second The second (0-59). Defaults to 0 (midnight)
     * @param int $millisecond The millisecond (0-999). Defaults to 0 (midnight)
     * @param TimeZoneContract|null $timeZone The timezone in which the date-time is expressed. Defaults to UTC.
     *
     * @return self
     */
    public static function create(int $year, Month $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, int $millisecond = 0, ?TimeZoneContract $timeZone = null): self
    {
        return new self($year, $month, $day, $hour, $minute, $second, $millisecond, $timeZone);
    }

    /** Create a DateTime object from a PHP DateTime or DateTimeImmutable object. */
    public static function fromDateTime(PhpDateTimeInterface $dateTime): self
    {
        return new self($dateTime->getTimestamp() * GregorianRatios::MillisecondsPerSecond + (int) $dateTime->format("v"));
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
        return new self($unixTimestamp * GregorianRatios::MillisecondsPerSecond);
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
        // always round down - to do otherwise would be to say the time's reached a given millisecond before it has
        return new self((int) floor(
            microtime(true) * (float) GregorianRatios::MillisecondsPerSecond,
        ));
    }

    /** Create a new DateTime with the time from the current DateTime and a specified date. */
    public function withDate(int $year, Month $month, int $day): static
    {
        // TODO these should be runtime exceptions
        assert(self::MinYear <= $year && self::MaxYear >= $year, new InvalidArgumentException("Expected year between -9999 and 9999 inclusive, found {$year}"));
        assert(self::MinDay <= $day && $month->dayCount($year) >= $day, new InvalidArgumentException("Expected day between 1 and {$month->dayCount($year)} inclusive, found {$day}"));

        if (!$this->isGregorianClean()) {
            // Ensure Gregorian time is correct when date components are set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->year = $year;
        $clone->month = $month;
        $clone->day = $day;
        $clone->clean &= ~self::CleanUnix;
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

    /** Fetch the weekday of the point in time. */
    public function weekday(): Weekday
    {
        $daysSinceEpoch = (int) floor($this->unixTimestampMs() / GregorianRatios::MillisecondsPerDay);
        $weekday = (UnixEpoch::Weekday->value + $daysSinceEpoch) % 7;

        if (0 > $weekday) {
            $weekday += 7;
        }

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
        // TODO these should be runtime exceptions
        assert(self::MinHour <= $hour && self::MaxHour >= $hour, new InvalidArgumentException("Expected hour between 0 and 23 inclusive, found {$hour}"));
        assert(self::MinMinute <= $minute && self::MaxMinute >= $minute, new InvalidArgumentException("Expected minute between 0 and 59 inclusive, found {$minute}"));
        assert(self::MinSecond <= $second && self::MaxSecond >= $second, new InvalidArgumentException("Expected second between 0 and 59 inclusive, found {$second}"));
        assert(self::MinMillisecond <= $millisecond && self::MaxMillisecond >= $millisecond, new InvalidArgumentException("Expected millisecond between 0 and 999 inclusive, found {$millisecond}"));

        if (!$this->isGregorianClean()) {
            // Ensure Gregorian date is correct when time components are set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->hour = $hour;
        $clone->minute = $minute;
        $clone->second = $second;
        $clone->millisecond = $millisecond;
        $clone->clean &= ~self::CleanUnix;
        return $clone;
    }

    /** Create a new DateTime with the date and time from the current DateTime, but with a specified hour. */
    public function withHour(int $hour): static
    {
        // TODO these should be runtime exceptions
        assert(self::MinHour <= $hour && self::MaxHour >= $hour, new InvalidArgumentException("Expected hour between 0 and 23 inclusive, found {$hour}"));

        if (!$this->isGregorianClean()) {
            // Ensure other Gregorian components are correct when hour is set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->hour = $hour;
        $clone->clean &= ~self::CleanUnix;
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
        // TODO these should be runtime exceptions
        assert(self::MinMinute <= $minute && self::MaxMinute >= $minute, new InvalidArgumentException("Expected minute between 0 and 59 inclusive, found {$minute}"));

        if (!$this->isGregorianClean()) {
            // Ensure other Gregorian components are correct when minute is set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->minute = $minute;
        $clone->clean &= ~self::CleanUnix;
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
        // TODO these should be runtime exceptions
        assert(self::MinSecond <= $second && self::MaxSecond >= $second, new InvalidArgumentException("Expected second between 0 and 59 inclusive, found {$second}"));

        if (!$this->isGregorianClean()) {
            // Ensure other Gregorian components are correct when second is set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->second = $second;
        $clone->clean &= ~self::CleanUnix;
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
        // TODO these should be runtime exceptions
        assert(self::MinMillisecond <= $millisecond && self::MaxMillisecond >= $millisecond, new InvalidArgumentException("Expected millisecond between 0 and 999 inclusive, found {$millisecond}"));

        if (!$this->isGregorianClean()) {
            // Ensure other Gregorian components are correct when millisecond is set
            $this->syncGregorian();
        }

        $clone = clone $this;
        $clone->millisecond = $millisecond;
        $clone->clean &= ~self::CleanUnix;
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
        return (int) floor($this->unixTimestampMs() / GregorianRatios::MillisecondsPerSecond);
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
    public function withTimeZone(TimeZoneContract $timeZone): static
    {
        if (!$this->isUnixClean()) {
            $this->syncUnix();
        }

        $clone = clone $this;
        $clone->timeZone = $timeZone;
        $clone->clean &= ~self::CleanGregorian;
        return $clone;
    }

    /** Fetch the timezone for the point in time. */
    public function timeZone(): TimeZoneContract
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
