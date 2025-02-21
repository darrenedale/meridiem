<?php

namespace Meridiem\Contracts;

use Meridiem\UtcOffset;

interface TimeZone
{
    /** The name of the timezone. */
    public function name(): string;

    /** The offset of the timezone from UTC, at a given point in time. */
    public function offset(DateTime $asAt): UtcOffset;

    public function currentOffset(): UtcOffset;

    public function hasTransitions(): bool;

    public function transitions(): array;
}
