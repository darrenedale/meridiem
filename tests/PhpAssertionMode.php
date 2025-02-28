<?php

namespace MeridiemTests;

/** Enumeration of possible PHP assertion modes. */
enum PhpAssertionMode: int
{
    /** Assertions are not compiled and not executed. */
    case Off = -1;

    /** Assertions are compiled but not executed. */
    case Inactive = 0;

    /** Assertions are compiled and executed. */
    case Active = 1;
}
