<?php

namespace Meridiem;

use ArrayAccess;
use InvalidArgumentException;

/** Test all items in an array satisfy a predicate. */
function all(iterable $subject, callable $predicate): bool
{
    foreach ($subject as $key => $value) {
        if (!$predicate($value, $key)) {
            return false;
        }
    }

    return true;
}

/**
 * @template T
 * @param iterable<T> $collection
 * @return T[]
 */
function cloneAll(iterable $collection): array
{
    return array_map(static fn (object $value): object => clone $value, $collection);
}

/**
 * @template T
 * @param iterable<T> $collection
 * @return T
 */
function first(iterable $collection): mixed
{
    foreach ($collection as $value) {
        return $value;
    }

    throw new InvalidArgumentException("Expected non-empty iterable");
}

/**
 * @template T
 * @param iterable<T> $collection
 * @return T
 */
function last(iterable $collection): mixed
{
    $iterated = false;

    foreach ($collection as $value) {
        $iterated = true;
    }

    if (!$iterated) {
        throw new InvalidArgumentException("Expected non-empty iterable");
    }

    return $value;
}
