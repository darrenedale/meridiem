<?php

namespace MeridiemTests;

use Meridiem\Month;
use Meridiem\PhpDateTimeFormatter;
use Meridiem\DateTime;
use PHPUnit\Framework\Attributes\DataProvider;

class PhpDateTimeFormatterTest extends TestCase
{
    private PhpDateTimeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new PhpDateTimeFormatter();
    }

    protected function tearDown(): void
    {
        unset($this->formatter);
    }

    public static function singleComponents(): iterable
    {
        // Individual date components
        yield "short-year" => [DateTime::create(2012, Month::August, 14), "y", "12"];
        yield "long-year" => [DateTime::create(1993, Month::March, 9), "Y", "1993"];
        yield "expanded-year-not-required" => [DateTime::create(1982, Month::January, 16), "x", "1982"];
        yield "expanded-year-always-CE" => [DateTime::create(1985, Month::October, 20), "X", "+1985"];
        yield "expanded-year-always-BCE" => [DateTime::create(-103, Month::April, 5), "X", "-0103"];
        yield "iso-week-year-typical" => [DateTime::create(2020, Month::June, 10), "o", "2020"];
        yield "iso-week-year-previous" => [DateTime::create(2016, Month::January, 1), "o", "2015"];
        yield "iso-week-year-next" => [DateTime::create(2024, Month::December, 31), "o", "2025"];
        yield "leap-year" => [DateTime::create(2000, Month::July, 19), "L", "1"];
        yield "non-leap-year" => [DateTime::create(1900, Month::November, 23), "L", "0"];
        
        foreach (Month::cases() as $month) {
            yield "full-month-{$month->name}" => [DateTime::create(1984, $month, 10), "F", $month->name];
            yield "abbreviated-month-{$month->name}" => [DateTime::create(1979, $month, 5), "M", substr($month->name, 0, 3)];
            yield "2-digit-month-{$month->name}" => [DateTime::create(1991, $month, 14), "m", sprintf("%02d", $month->value)];
            yield "minimal-digit-month-{$month->name}" => [DateTime::create(1938, $month, 23), "n", (string) $month->value];
            yield "days-in-month-{$month->name}-leap" => [DateTime::create(2000, $month, 29), "t", (string) $month->dayCount(2000)];
            yield "days-in-month-{$month->name}-non-leap" => [DateTime::create(2003, $month, 21), "t", (string) $month->dayCount(2003)];
        }

        yield "day-name-monday" => [DateTime::create(1996, Month::May, 6), "l", "Monday"];
        yield "day-name-tuesday" => [DateTime::create(2023, Month::July, 11), "l", "Tuesday"];
        yield "day-name-wednesday" => [DateTime::create(2013, Month::February, 20), "l", "Wednesday"];
        yield "day-name-thursday" => [DateTime::create(2001, Month::September, 20), "l", "Thursday"];
        yield "day-name-friday" => [DateTime::create(1963, Month::October, 11), "l", "Friday"];
        yield "day-name-saturday" => [DateTime::create(2017, Month::June, 10), "l", "Saturday"];
        yield "day-name-sunday" => [DateTime::create(1987, Month::August, 2), "l", "Sunday"];

        yield "day-abbreviated-name-monday" => [DateTime::create(1996, Month::May, 6), "D", "Mon"];
        yield "day-abbreviated-name-tuesday" => [DateTime::create(2023, Month::July, 11), "D", "Tue"];
        yield "day-abbreviated-name-wednesday" => [DateTime::create(2013, Month::February, 20), "D", "Wed"];
        yield "day-abbreviated-name-thursday" => [DateTime::create(2001, Month::September, 20), "D", "Thu"];
        yield "day-abbreviated-name-friday" => [DateTime::create(1963, Month::October, 11), "D", "Fri"];
        yield "day-abbreviated-name-saturday" => [DateTime::create(2017, Month::June, 10), "D", "Sat"];
        yield "day-abbreviated-name-sunday" => [DateTime::create(1987, Month::August, 2), "D", "Sun"];

        yield "iso-day-monday" => [DateTime::create(1996, Month::May, 6), "N", "1"];
        yield "iso-day-tuesday" => [DateTime::create(2023, Month::July, 11), "N", "2"];
        yield "iso-day-wednesday" => [DateTime::create(2013, Month::February, 20), "N", "3"];
        yield "iso-day-thursday" => [DateTime::create(2001, Month::September, 20), "N", "4"];
        yield "iso-day-friday" => [DateTime::create(1963, Month::October, 11), "N", "5"];
        yield "iso-day-saturday" => [DateTime::create(2017, Month::June, 10), "N", "6"];
        yield "iso-day-sunday" => [DateTime::create(1987, Month::August, 2), "N", "7"];

        yield "day-of-wee-number-sunday" => [DateTime::create(1987, Month::August, 2), "w", "0"];
        yield "day-of-wee-number-monday" => [DateTime::create(1996, Month::May, 6), "w", "1"];
        yield "day-of-wee-number-tuesday" => [DateTime::create(2023, Month::July, 11), "w", "2"];
        yield "day-of-wee-number-wednesday" => [DateTime::create(2013, Month::February, 20), "w", "3"];
        yield "day-of-wee-number-thursday" => [DateTime::create(2001, Month::September, 20), "w", "4"];
        yield "day-of-wee-number-friday" => [DateTime::create(1963, Month::October, 11), "w", "5"];
        yield "day-of-wee-number-saturday" => [DateTime::create(2017, Month::June, 10), "w", "6"];

        for ($day = 1; $day <= 31; ++$day) {
            yield "day-of-month-{$day}-2-digits" => [DateTime::create(1945, Month::August, $day), "d", sprintf("%02d", $day)];
            yield "day-of-month-{$day}-minimal-digits" => [DateTime::create(1973, Month::December, $day), "j", (string) $day];

            if (1 === $day % 10 && 1 !== (int) floor($day / 10)) {
                $expected = "st";
            } else if (2 === $day % 10 && 1 !== (int) floor($day / 10)) {
                $expected = "nd";
            } else if (3 === $day % 10 && 1 !== (int) floor($day / 10)) {
                $expected = "rd";
            } else {
                $expected = "th";
            }

            yield "day-of-month-{$day}-ordinal-suffix" => [DateTime::create(1945, Month::August, $day), "S", $expected];
        }

        yield "day-of-leap-year-1st-jan" => [DateTime::create(1984, Month::January, 1), "z", "0"];
        yield "day-of-leap-year-31st-jan" => [DateTime::create(1984, Month::January, 31), "z", "30"];
        yield "day-of-leap-year-1st-feb" => [DateTime::create(1984, Month::February, 1), "z", "31"];
        yield "day-of-leap-year-29th-feb" => [DateTime::create(1984, Month::February, 29), "z", "59"];
        yield "day-of-leap-year-1st-mar" => [DateTime::create(1984, Month::March, 1), "z", "60"];
        yield "day-of-leap-year-31sr-mar" => [DateTime::create(1984, Month::March, 31), "z", "90"];
        yield "day-of-leap-year-1st-apr" => [DateTime::create(1984, Month::April, 1), "z", "91"];
        yield "day-of-leap-year-30th-apr" => [DateTime::create(1984, Month::April, 30), "z", "120"];
        yield "day-of-leap-year-1st-may" => [DateTime::create(1984, Month::May, 1), "z", "121"];
        yield "day-of-leap-year-31st-may" => [DateTime::create(1984, Month::May, 31), "z", "151"];
        yield "day-of-leap-year-1st-jun" => [DateTime::create(1984, Month::June, 1), "z", "152"];
        yield "day-of-leap-year-30th-jun" => [DateTime::create(1984, Month::June, 30), "z", "181"];
        yield "day-of-leap-year-1st-jul" => [DateTime::create(1984, Month::July, 1), "z", "182"];
        yield "day-of-leap-year-31st-jul" => [DateTime::create(1984, Month::July, 31), "z", "212"];
        yield "day-of-leap-year-1st-aug" => [DateTime::create(1984, Month::August, 1), "z", "213"];
        yield "day-of-leap-year-31st-aug" => [DateTime::create(1984, Month::August, 31), "z", "243"];
        yield "day-of-leap-year-1st-sep" => [DateTime::create(1984, Month::September, 1), "z", "244"];
        yield "day-of-leap-year-30th-sep" => [DateTime::create(1984, Month::September, 30), "z", "273"];
        yield "day-of-leap-year-1st-oct" => [DateTime::create(1984, Month::October, 1), "z", "274"];
        yield "day-of-leap-year-31st-oct" => [DateTime::create(1984, Month::October, 31), "z", "304"];
        yield "day-of-leap-year-1st-nov" => [DateTime::create(1984, Month::November, 1), "z", "305"];
        yield "day-of-leap-year-3oth-nov" => [DateTime::create(1984, Month::November, 30), "z", "334"];
        yield "day-of-leap-year-1st-dec" => [DateTime::create(1984, Month::December, 1), "z", "335"];
        yield "day-of-leap-year-31st-dec" => [DateTime::create(1984, Month::December, 31), "z", "365"];

        // non-leap year
        yield "day-of-non-leap-year-28th-feb" => [DateTime::create(1991, Month::February, 28), "z", "58"];
        yield "day-of-non-leap-year-1st-mar" => [DateTime::create(1991, Month::March, 1), "z", "59"];

        $dateTime = DateTime::create(2009, Month::January, 1);

        for ($week = 1; $week <= 53 ; ++$week) {
            yield "iso-week-of-year-$week" => [$dateTime, "W", sprintf("%02d", $week)];
            $day = $dateTime->day() + 7;

            if ($day > $dateTime->month()->dayCount(2009)) {
                $dateTime = $dateTime->withDate(
                    2009,
                    $dateTime->month()->next(),
                    $day - $dateTime->month()->dayCount(2009),
                );
            } else {
                $dateTime = $dateTime->withDate(2009, $dateTime->month(), $day);
            }
        }

        // Individual time components
        yield "am-lowercase" => [DateTime::create(2007, Month::May, 15, 11, 59, 59), "a", "am"];
        yield "pm-lowercase" => [DateTime::create(2009, Month::August, 7, 12, 0, 0), "a", "pm"];

        yield "AM-uppercase" => [DateTime::create(2002, Month::September, 10, 8, 31, 16), "A", "AM"];
        yield "PM-uppercase" => [DateTime::create(1998, Month::July, 30, 12, 0, 0), "A", "PM"];

        // swatch time is always UTC+1
        yield "swatch-zero" => [DateTime::create(1999, Month::October, 23, 23, 0, 0), "B", "000"];
        yield "swatch-one-hour" => [DateTime::create(1991, Month::December, 22, 0, 0), "B", "041"];
        yield "swatch-end" => [DateTime::create(1987, Month::May, 3, 22, 59), "B", "999"];

        // Hour component
        for ($hour = 0; $hour < 23; ++$hour) {
            $expected = (0 === ($hour % 12) ? 12 : $hour % 12);
            yield "minimal-digits-hour-12-{$hour}" => [DateTime::create(1994, Month::March, 14, $hour, 32, 17), "g", (string) $expected,];
            yield "minimal-digits-hour-24-{$hour}" => [DateTime::create(1977, Month::June, 19, $hour, 19, 12), "G", (string) $hour,];
            yield "2-digits-hour-12-{$hour}" => [DateTime::create(1983, Month::August, 27, $hour, 47, 33), "h", sprintf("%02d", $expected),];
            yield "2-digits-hour-24-{$hour}" => [DateTime::create(1972, Month::November, 21, $hour, 51, 26), "H", sprintf("%02d", $hour),];
        }

        for ($minuteSecond = 0; $minuteSecond < 60; ++$minuteSecond) {
            yield "2-digits-minute-{$minuteSecond}" => [DateTime::create(1985, Month::October, 27, 9, $minuteSecond, 31), "i", sprintf("%02d", $minuteSecond),];
            yield "2-digits-second-{$minuteSecond}" => [DateTime::create(1996, Month::September, 19, 18, 17, $minuteSecond), "s", sprintf("%02d", $minuteSecond),];
        }

        // TODO u
        // TODO v


        // Whole-date-time formats

        // Common user formats
        yield "iso-8601" => [
            DateTime::create(2023, Month::April, 16, 14, 8, 38, 959),
            "Y-m-d\\TH:i:sP",
            "2023-04-16T14:08:38+00:00",
        ];

        yield "local-date-time-no-ms" => [
            DateTime::create(2021, Month::June, 4, 7, 41, 6),
            "Y-m-d H:i:s",
            "2021-06-04 07:41:06",
        ];

        yield "local-date-time-with-ms" => [
            DateTime::create(1982, Month::January, 25, 19, 20, 46, 661),
            "Y-m-d H:i:s.v",
            "1982-01-25 19:20:46.661",
        ];

        yield "human-eu" => [
            DateTime::create(1997, Month::March, 12, 4, 6, 3, 12),
            "H:i:s.v, d/m/Y",
            "04:06:03.012, 12/03/1997",
        ];

        yield "human-us" => [
            DateTime::create(2001, Month::November, 16, 15, 30, 28, 218),
            "H:i:s.v, m/d/Y",
            "15:30:28.218, 11/16/2001",
        ];
    }

    /** Ensure individual components format as expected. */
    #[DataProvider("singleComponents")]
    public function testFormat1(DateTime $testDateTime, string $format, string $expected)
    {
        self::assertSame($expected, $this->formatter->format($testDateTime, $format));
    }
}
