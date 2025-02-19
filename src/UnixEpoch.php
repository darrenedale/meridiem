<?php

namespace Meridiem;

/** Definition of the point in time of the Unix epoch. */
final class UnixEpoch
{
    public const int Year = 1970;

    public const Month Month = Month::January;

    public const int Day = 1;

    public const int Hour = 0;

    public const int Minute = 0;

    public const int Second = 0;

    public const int Millisecond = 0;

    public const Weekday Weekday = Weekday::Thursday;

    public static function dateTime(): DateTime
    {
        static $epoch = null;

        if (null === $epoch) {
            $epoch = DateTime::create(self::Year, self::Month, self::Day, self::Hour, self::Minute, self::Second, self::Millisecond);
        }

        return $epoch;
    }
}
