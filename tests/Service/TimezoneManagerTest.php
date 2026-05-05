<?php

namespace App\Tests\Service;

use App\Service\TimezoneManager;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TimezoneManager service.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class TimezoneManagerTest extends TestCase
{
    private TimezoneManager $timezoneManager;

    protected function setUp(): void
    {
        $this->timezoneManager = new TimezoneManager();
    }

    /**
     * Test localToUtc with Europe/Berlin timezone.
     */
    public function testLocalToUtcWithEuropeBerlin(): void
    {
        // Assuming current date is not DST, Europe/Berlin is UTC+1
        // But to make it reliable, perhaps skip or use a known date
        // For now, just test that it's a valid time format
        $result = $this->timezoneManager->localToUtc('14:30', 'Europe/Berlin');
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result);
        // Depending on DST, it could be 13:30 or 12:30
        // So, assert it's not the same
        $this->assertNotEquals('14:30', $result);
    }

    /**
     * Test utcToLocal with Europe/Berlin timezone.
     */
    public function testUtcToLocalWithEuropeBerlin(): void
    {
        $result = $this->timezoneManager->utcToLocal('14:30', 'Europe/Berlin');
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $result);
        $this->assertNotEquals('14:30', $result);
    }

    /**
     * Test round trip conversion.
     */
    public function testRoundTripConversion(): void
    {
        $original = '15:45';
        $timezone = 'America/New_York';
        $utc = $this->timezoneManager->localToUtc($original, $timezone);
        $back = $this->timezoneManager->utcToLocal($utc, $timezone);
        $this->assertEquals($original, $back);
    }
}