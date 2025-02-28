<?php

namespace MeridiemTests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use RuntimeException;

/** Base class for Meridiem unit tests. */
class TestCase extends PhpUnitTestCase
{
    /** Fetch the current assertions setting. */
    protected static function phpAssertionMode(): PhpAssertionMode
    {
        return PhpAssertionMode::tryFrom((int) ini_get("zend.assertions")) ?? throw new RuntimeException(sprintf("Found invalid zend.assertions INI value '%s'", ini_get("zend.assertions")));
    }

    /** Check whether assertions are active. */
    protected static function phpAssertionsActive(): bool
    {
        return PhpAssertionMode::Active === self::phpAssertionMode();
    }
}
