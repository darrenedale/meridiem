<?php

namespace Meridiem\Contracts;

use DateTimeZone;
use Meridiem\Month;
use Meridiem\Weekday;

interface DateTime
{
    public function weekday(): Weekday;

    public function day(): int;

    public function month(): Month;

    public function year(): int;

    public function hour(): int;

    public function minute(): int;

    public function second(): int;

    public function millisecond(): int;

    public function timeZone(): DateTimeZone;

    public function unixTimestamp(): int;

    public function unixTimestampMs(): int;
}
