<?php

namespace Meridiem;

use InvalidArgumentException;

/** Representation of an offset from UTC.*/
class UtcOffset
{
    private const int MillisecondsPerSecond = 1000;

    private const int MillisecondsPerMinute = 60 * self::MillisecondsPerSecond;

    private const int MillisecondsPerHour = 60 * self::MillisecondsPerMinute;

    private const int SecondsPerMinute = 60;

    private const int SecondsPerHour = 60 * self::SecondsPerMinute;

    private int $hours;

    private int $minutes;

    public function __construct(int $hours, int $minutes)
    {
        // 0 hours or 0 minutes are compatible whatever the other arg is; otherwise, the signs must be the same
        assert(0 === $hours || 0 === $minutes || (0 > $hours === 0 > $minutes), new InvalidArgumentException("Expected hours and minutes with compatible signed, found {$hours} and {$minutes}"));
        assert(-60 < $minutes && 60 > $minutes, new InvalidArgumentException("Expected minutes between -59 and 59 inclusive, found {$minutes}"));

        // TODO can we assert range of $hours?
        $this->hours = $hours;
        $this->minutes = $minutes;
    }

    public function hours(): int
    {
        return $this->hours;
    }

    public function minutes(): int
    {
        return $this->hours;
    }

    public function offset(bool $includeColon = false): string
    {
        return sprintf(
            "%s%02d%s%02d",
            (0 > $this->hours || 0 > $this->minutes) ? "-" : "+",
            abs($this->hours),
            $includeColon ? ":" : "",
            abs($this->minutes),
        );
    }

    public function offsetSeconds(): int
    {
        return self::SecondsPerHour * $this->hours() + self::SecondsPerMinute * $this->minutes();
    }

    public function offsetMilliseconds(): int
    {
        return self::MillisecondsPerHour * $this->hours() + self::MillisecondsPerMinute * $this->minutes();
    }

    public static function parse(string $offset): self
    {
        if (!preg_match($offset, "/^(+-)(\d{2}):?(\d{2})\$/", $captures)) {
            throw new InvalidArgumentException("Expected valid timezone offset, found \"{$offset}\"");
        }

        [, $sign, $hours, $minutes] = $captures;

        if ("-" === $sign) {
            $hours = -$hours;
            $minutes = -$minutes;
        }

        return new self($hours, $minutes);
    }
}
