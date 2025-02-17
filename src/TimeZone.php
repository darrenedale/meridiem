<?php

namespace Meridiem;

use Meridiem\Contracts\DateTime;
use Meridiem\Contracts\TimeZone as TimeZoneContract;

/** TODO very much a WIP ATM */
class TimeZone implements TimeZoneContract
{
    private string $name;

    /** @var TimeZoneTransition[] */
    private array $transitions;

    private function __construct(string $name, array $transitions = [])
    {
        $this->name = $name;
        $this->transitions = $transitions;
    }

    public function name(): string
    {
        // TODO: Implement name() method.
    }

    public function offset(DateTime $asAt): UtcOffset
    {
        // TODO: Implement offset() method.
    }

    public function currentOffset(bool $dst = false): UtcOffset
    {
        // TODO: Implement currentOffset() method.
    }


    public function hasTransitions(): bool
    {
        // TODO: Implement hasTransitions() method.
    }

    /** @return TimeZoneTransition[] */
    public function transitions(): array
    {
        // TODO: Implement transitions() method.
    }

    public static function parse(string $timeZone): TimeZone
    {
        // TODO: Implement transitions() method.
    }

    public static function lookup(string $timeZoneName): TimeZone
    {
        // TODO: Implement transitions() method.
    }
}
