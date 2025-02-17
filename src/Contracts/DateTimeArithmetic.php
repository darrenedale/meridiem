<?php

namespace Meridiem\Contracts;

use DateInterval;

/** Interface for DateTime objects that perform arithmetic on the dates and times they represent. */
interface DateTimeArithmetic
{
    public function add(DateInterval $interval): DateTime;

    public function subtract(DateInterval $interval): DateTime;

    public function addMilliseconds(int $milliseconds): DateTime;

    public function addSeconds(int $seconds): DateTime;

    public function addMinutes(int $minutes): DateTime;

    public function addHours(int $hours): DateTime;

    public function addDays(int $days): DateTime;

    public function addMonths(int $months): DateTime;

    public function addYears(int $years): DateTime;

    public function subtractMilliseconds(int $milliseconds): DateTime;

    public function subtractSeconds(int $seconds): DateTime;

    public function subtractMinutes(int $minutes): DateTime;

    public function subtractHours(int $hours): DateTime;

    public function subtractDays(int $days): DateTime;

    public function subtractMonths(int $months): DateTime;

    public function subtractYears(int $years): DateTime;
}
