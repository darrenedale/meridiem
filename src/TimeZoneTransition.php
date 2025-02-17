<?php

namespace Meridiem;

use InvalidArgumentException;
use Meridiem\Contracts\DateTime as DateTimeContract;
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

    /** @var UtcOffset The offset from UTC after the transition. */
    private UtcOffset $utcOffset;

    public function __construct(int $fromYear, int $toYear, Month $month, int|TimeZoneTransitionDay $day, int $hour, UtcOffset $utcOffset)
    {
        assert ($fromYear >= $toYear, new InvalidArgumentException("Expected to year on or after from year, found from {$fromYear} and to {$toYear}"));
        $this->fromYear = $fromYear;
        $this->toYear = $toYear;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->utcOffset = $utcOffset;
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

    public function utcOffset(): UtcOffset
    {
        return $this->utcOffset;
    }

    public function appliesTo(DateTimeContract $dateTime): bool
    {
        return $this->fromYear() <= $dateTime->year()
            && $this->toYear() >= $dateTime->year()
            && $this->month()->value <= $dateTime->month()->value
            ;
    }
}
