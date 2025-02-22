<?php

namespace Meridiem;

use InvalidArgumentException;
use Meridiem\Contracts\TimeZoneTransitionDay;

/** Defines when a timezone transition(s|ed) between SDT and DST. */
class TimeZoneTransition
{
    public const int YearOngoing = PHP_INT_MAX;

    /** @var int The first year the transition occur(s|red) at this time. */
    private int $fromYear;

    /** @var int The last year the transition occur(s|red) at this time (or PHP_INT_MAX if it's still in effect). */
    private int $toYear;

    /** @var Month The month in which the transition occur(s|red). */
    private Month $month;

    /**
     * @var int|TimeZoneTransitionDay The day in which the transition occur(s|red).
     *
     * This will be an int if it's always on a fixe date; otherwise the TimeZoneTransitionDay will calculate the date
     * of transition, given a year.
     */
    private int|TimeZoneTransitionDay $day;

    /** @var int The hour at which the transition occur(s|red). */
    private int $hour;

    /** @var int The number of minutes of daylight saving, compared to the timezone's base offset. */
    private int $savingMinutes;

    public function __construct(int $fromYear, int $toYear, Month $month, int|TimeZoneTransitionDay $day, int $hour, int $savingMinutes)
    {
        assert($fromYear <= $toYear, new InvalidArgumentException("Expected to year on or after from year, found from {$fromYear} and to {$toYear}"));
        $this->fromYear = $fromYear;
        $this->toYear = $toYear;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->savingMinutes = $savingMinutes;
    }

    public function fromYear(): int
    {
        return $this->fromYear;
    }

    public function toYear(): int
    {
        return $this->toYear;
    }

    public function month(): Month
    {
        return $this->month;
    }

    public function day(): int|TimeZoneTransitionDay
    {
        return $this->day;
    }

    public function hour(): int
    {
        return $this->hour;
    }

    public function dayForYear(int $year): int
    {
        assert(
            $year >= $this->fromYear() && $year <= $this->toYear(),
            new InvalidArgumentException(
                sprintf(
                    "Expected year within range %04d-%s, found %04d",
                    $this->fromYear(),
                    self::YearOngoing === $this->toYear() ? "" : sprintf("%04d", $this->toYear()),
                    $year,
                )
            )
        );

        $transitionDay = $this->day();
        return (is_int($transitionDay) ? $transitionDay : $transitionDay->dayFor($year, $this->month()));
    }

    public function savingMinutes(): int
    {
        return $this->savingMinutes;
    }

    public function applyToOffset(UtcOffset $offset): UtcOffset
    {
        return new UtcOffset(
            $offset->hours() + (int) floor($this->savingMinutes / 60),
            $offset->minutes() + $this->savingMinutes % 60,
        );
    }
}
