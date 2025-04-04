<?php

namespace Meridiem;

/**
 * Static class with constants for the ratios between components in Gregorian date-times.
 *
 * All components (Year, month, ...) are expressed in milliseconds as this is the precision to which Meridiem represents
 * points in time, but some other useful ratios are also present.
 */
final class GregorianRatios
{
    public const int MillisecondsPerSecond = 1000;

    public const int SecondsPerMinute = 60;

    public const int MinutesPerHour = 60;

    public const int HoursPerDay = 24;

    public const int DaysPerYear = 365;

    public const int MillisecondsPerMinute = self::MillisecondsPerSecond * self::SecondsPerMinute;

    public const int MillisecondsPerHour = self::MillisecondsPerMinute * self::MinutesPerHour;

    public const int MillisecondsPerDay = self::MillisecondsPerHour * self::HoursPerDay;

    public const int MillisecondsPerYear = self::MillisecondsPerDay * self::DaysPerYear;

    public const int MillisecondsPerLeapYear = self::MillisecondsPerYear + self::MillisecondsPerDay;

    public const int SecondsPerHour = self::SecondsPerMinute * self::MinutesPerHour;
}
