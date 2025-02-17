<?php

namespace Meridiem\Contracts;

/** Interface for DateTime objects that can compare themselves to others. */
interface DateTimeComparison
{
    public function isBefore(DateTime $other): bool;

    public function isAfter(DateTime $other): bool;

    public function isEqualTo(DateTime $other): bool;

    public function isInSameYearAs(DateTime $other): bool;

    public function isInSameMonthAs(DateTime $other): bool;

    public function isOnSameDayAs(DateTime $other): bool;

    public function isInSameHourAs(DateTime $other): bool;

    public function isInSameMinuteAs(DateTime $other): bool;

    public function isInSameSecondAs(DateTime $other): bool;
}
