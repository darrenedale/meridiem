<?php

namespace Meridiem;

use InvalidArgumentException;
use Meridiem\Contracts\DateTime as DateTimeContract;
use Meridiem\Contracts\TimeZone as TimeZoneContract;
use Meridiem\Contracts\TimeZoneTransitionDay;
use RuntimeException;

/** TODO very much a WIP ATM */
class TimeZone implements TimeZoneContract
{
    private string $name;

    /** @var TimeZoneTransition[] */
    private array $transitions;

    /** @var UtcOffset TimeZone's standard offset from UTC */
    private UtcOffset $sdtOffset;

    /**
     *
     * @param string $name
     * @param array|null $transitions Transitions are required to be ordered chronologically by fromYear().
     */
    private function __construct(string $name, UtcOffset $sdtOffset, ?array $transitions = null)
    {
        $transitions ??= [new TimeZoneTransition(-9999, TimeZoneTransition::YearOngoing, Month::January, 1, 0, 0)];
        assert(0 < count($transitions), new InvalidArgumentException("Expected at least one transition, found none"));
        assert(all($transitions, static fn(mixed $transition): bool => $transition instanceof TimeZoneTransitionDay), new InvalidArgumentException("Expected array of TimeZoneTransitionDay objects"));
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
        $transitions = $this->transitionsFor($asAt->year());

        if (0 === count($transitions)) {
            return $this->sdtOffset;
        }

        // iterating in reverse chronological order makes it easier to identify the one that applies
        for ($idx = count($transitions) - 1; $idx >= 0; --$idx) {
            $transition = $transitions[$idx];

            if ($transition->month() < $asAt->month()) {
                break;
            }

            if ($transition->month() === $asAt->month()) {
                $transitionDay = $transition->day();
                $transitionDay = ($transitionDay instanceof TimeZoneTransitionDay ? $transitionDay->dayFor($asAt->year(), $asAt->month()) : $transitionDay);

                if ($transitionDay < $asAt->day()) {
                    break;
                }

                if ($transitionDay === $asAt->day() && $transition->hour() <= $asAt->hour()) {
                    break;
                }
            }
        }

        if (-1 === $idx) {
            // if none of the transitions from the year apply, we need the last one from the previous year
            $transitions = $this->transitionsFor($asAt->year() - 1);

            if (0 === count($transitions)) {
                return $this->sdtOffset;
            }

            $transition = last($transitions);
        }

        return $transition->applyToOffset($this->sdtOffset());
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

    public static function parse(string $timeZone): TimeZone
    {
        return new TimeZone($timeZone, UtcOffset::parse($timeZone));
    }

    public static function lookup(string $timeZoneName): TimeZone
    {
        return match ($timeZoneName) {
            "UTC" => new self("UTC", new UtcOffset(0, 0)),
        };
    }
}
