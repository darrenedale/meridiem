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

    private UtcOffset $baseOffset;

    private function __construct(string $name, UtcOffset $baseOffset, array $transitions = [])
    {
        $this->name = $name;
        // TODO clone transitions
        $this->transitions = $transitions;
        $this->baseOffset = clone $baseOffset;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function offset(DateTime $asAt): UtcOffset
    {
        return $this->baseOffset;
    }

    public function currentOffset(bool $dst = false): UtcOffset
    {
        // TODO: Implement currentOffset() method.
    }


    public function hasTransitions(): bool
    {
        return 0 < count($this->transitions);
    }

    /** @return TimeZoneTransition[] */
    public function transitions(): array
    {
        return $this->transitions;
    }

    public static function parse(string $timeZone): TimeZone
    {
        // TODO: Implement transitions() method.
    }

    public static function lookup(string $timeZoneName): TimeZone
    {
        return match ($timeZoneName) {
            "UTC" => new self("UTC", new UtcOffset(0, 0)),
        };
    }
}
