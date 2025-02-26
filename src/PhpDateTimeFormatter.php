<?php

namespace Meridiem;

use DateTime as PhpDateTime;
use DateTimeZone as PhpDateTimeZone;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\Contracts\DateTimeFormatter;

/** DateTime formatter that formats dates and times using PHP date string formatting. */
class PhpDateTimeFormatter implements DateTimeFormatter
{
    public function format(DateTimeContract $dateTime, string $format): string
    {
        return (new PhpDateTime(timezone: new PhpDateTimeZone($dateTime->timeZone()->name())))
            ->setDate($dateTime->year(), $dateTime->month()->value, $dateTime->day())
            ->setTime($dateTime->hour(), $dateTime->minute(), $dateTime->second(), 1000 * $dateTime->millisecond())
            ->format($format);
    }

    public function parse(string $dateTime, string $format): DateTime
    {
        return DateTime::fromDateTime(PhpDateTime::createFromFormat($format, $dateTime));
    }
}
