<?php

namespace Meridiem;

/** Enumeration of weekdays in the Gregorian calendar. */
enum Weekday: int
{
    case Monday = 0;
    case Tuesday = 1;
    case Wednesday = 2;
    case Thursday = 3;
    case Friday = 4;
    case Saturday = 5;
    case Sunday = 6;

    /** Determine if this Weekday is before another in the week. */
    public function isBefore(Weekday $weekday): bool
    {
        return $this->value < $weekday->value;
    }

    /** Determine if this Weekday is before after in the week. */
    public function isAfter(Weekday $weekday): bool
    {
        return $weekday->value < $this->value;
    }

    /** Fetch the weekday a number of days after this day. */
    public function advance(int $days): Weekday
    {
        assert(0 <= $days, "Expected days >= 0, found {$days}");
        return Weekday::from(($this->value + $days) % 7);
    }

    /** Fetch the weekday a number of days before this day. */
    public function back(int $days): Weekday
    {
        assert(0 <= $days, "Expected days >= 0, found {$days}");
        return Weekday::from((7 + $this->value - ($days % 7)) % 7);
    }

    /** Count the number of days forward from this day to another. */
    public function distanceTo(Weekday $weekday): int
    {
        if ($this->isAfter($weekday)) {
            return (7 + $weekday->value) - $this->value;
        }

        return $weekday->value - $this->value;
    }

    /** Count the number of days forward from another day to this day. */
    public function distanceFrom(Weekday $weekday): int
    {
        return $weekday->distanceTo($this);
    }
}
