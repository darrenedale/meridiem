<?php

namespace Meridiem\Contracts;

use Meridiem\UtcOffset;

interface TimeZone
{
    /** The name of the timezone. */
    public function name(): string;

    /** The offset of the timezone from UTC, at a given point in time. */
    public function offset(DateTime $asAt): UtcOffset;

    /**
     * The currently defined offset of the timezone from UTC.
     *
     * The standard time offset from UTC as currently defined is returned, unless the $dst parameter is used.
     *
     * @param bool $dst Retrieve the defined Daylight Savings Time offset (if the timezone has one). If this is true but
     * the timezone doesn't currently distinguish between standard time and daylight savings, the standard offset is
     * returned.
     */
    public function currentOffset(bool $dst = false): UtcOffset;

    public function hasTransitions(): bool;

    public function transitions(): array;
}
