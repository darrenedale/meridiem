<?php

namespace Meridiem;

use InvalidArgumentException;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\Contracts\TimeZone as TimeZoneContract;
use Meridiem\Contracts\TimeZoneTransitionDay;

/** TODO very much a WIP ATM */
class TimeZone implements TimeZoneContract
{
    private string $name;

    /** @var TimeZoneTransition[] */
    private array $transitions;

    /** @var UtcOffset TimeZone's standard offset from UTC */
    private UtcOffset $sdtOffset;

    /**
     * @param string $name
     * @param UtcOffset $sdtOffset The standard offset from UTC for the timezone.
     * @param TimeZoneTransition[] $transitions Transitions indicating when DST starts/stops for the timezone.
     */
    private function __construct(string $name, UtcOffset $sdtOffset, array $transitions = [])
    {
        assert(all($transitions, static fn(mixed $transition): bool => $transition instanceof TimeZoneTransition), new InvalidArgumentException("Expected array of TimeZoneTransition objects"));
        $this->name = $name;
        $this->sdtOffset = $sdtOffset;
        $this->transitions = cloneAll($transitions);
    }

    /**
     * Fetch the transitions that apply for a given year.
     *
     * @param int $year The year whose transitions are sought.
     *
     * @return TimeZoneTransition[] The transitions in chronological order.
     */
    protected function transitionsFor(int $year): array
    {
        $transitions = array_filter(
            $this->transitions,
            static fn (TimeZoneTransition $transition): bool => $transition->fromYear() <= $year && $transition->toYear() >= $year,
        );

        usort($transitions, static fn(TimeZoneTransition $first, TimeZoneTransition $second): int => $second->month() <=> $first->month());
        return $transitions;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function sdtOffset(): UtcOffset
    {
        return $this->sdtOffset;
    }

    /**
     * Fetches the offset that applies for the timezone at a given point in time.
     *
     * The returned offset takes account of the transitions for the timezone. If there are no transitions, or none that
     * apply to the given point in time, the timezone's base offset is returned.
     */
    public function offset(DateTimeContract $asAt): UtcOffset
    {
        return $this->effectiveTransition($asAt)?->applyToOffset($this->sdtOffset()) ?? $this->sdtOffset();
    }

    public function currentOffset(): UtcOffset
    {
        return $this->offset(DateTime::now()->withTimeZone($this));
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

    public function effectiveTransition(DateTimeContract $asAt): ?TimeZoneTransition
    {
        $transitions = $this->transitionsFor($asAt->year());

        if (0 === count($transitions)) {
            return null;
        }

        // iterating in reverse chronological order makes it easier to identify the one that applies
        for ($idx = count($transitions) - 1; $idx >= 0; --$idx) {
            $transition = $transitions[$idx];

            if ($transition->month() < $asAt->month()) {
                return $transition;
            }

            if ($transition->month() === $asAt->month()) {
                $transitionDay = $transition->day();
                $transitionDay = ($transitionDay instanceof TimeZoneTransitionDay ? $transitionDay->dayFor($asAt->year(), $asAt->month()) : $transitionDay);

                if ($transitionDay < $asAt->day()) {
                    return $transition;
                }

                if ($transitionDay === $asAt->day() && $transition->hour() <= $asAt->hour()) {
                    return $transition;
                }
            }
        }

        if (-1 !== $idx) {
            return $transition;
        }

        // if none of the transitions from the year apply, we need the last one from the previous year
        $transitions = $this->transitionsFor($asAt->year() - 1);

        if (0 < count($transitions)) {
            return last($transitions);
        }

        return null;
    }

    public static function parse(string $timeZone): TimeZone
    {
        return new TimeZone($timeZone, UtcOffset::parse($timeZone));
    }

    public static function lookup(string $timeZoneName): TimeZone
    {
        return match ($timeZoneName) {
            "UTC" => new self("UTC", new UtcOffset(0, 0)),
            "America/New_York" => new self("America/New_York", new UtcOffset(0, 0)),
            "Europe/London" => new self("Europe/London", new UtcOffset(0, 0)),
        };
    }
}
