<?php

namespace App\Service;

use DateTime;
use DateTimeZone;

/**
 * Service for converting times between local timezones and UTC.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class TimezoneManager
{
    
    /**
     * Converts a local time to UTC time.
     *
     * @param string $time The time in H:i format (e.g., "14:30")
     * @param string $timezone The timezone identifier (e.g., "Europe/Berlin")
     * @return string The UTC time in H:i format
     */
    public function localToUtc(string $time, string $timezone): string
    {
        $date = new DateTime($time, new DateTimeZone($timezone));
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date->format('H:i');
    }

    
    /**
     * Converts a UTC time to local time.
     *
     * @param string $utcTime The UTC time in H:i format (e.g., "12:30")
     * @param string $timezone The timezone identifier (e.g., "Europe/Berlin")
     * @return string The local time in H:i format
     */
    public function utcToLocal(string $utcTime, string $timezone): string
    {
        $date = new DateTime($utcTime, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($timezone));
        return $date->format('H:i');
    }
}
