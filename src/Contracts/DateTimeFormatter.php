<?php

namespace Meridiem\Contracts;

interface DateTimeFormatter
{
    public function format(DateTime $dateTime, string $format): string;
    public function parse(string $dateTime, string $format): DateTime;
}
