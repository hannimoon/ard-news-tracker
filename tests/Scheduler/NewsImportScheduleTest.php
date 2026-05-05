<?php

namespace App\Tests\Scheduler;

use App\Repository\CronConfigRepository;
use App\Scheduler\NewsImportSchedule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NewsImportSchedule.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
class NewsImportScheduleTest extends TestCase
{
    private NewsImportSchedule $schedule;
    private CronConfigRepository $cronConfigRepository;

    protected function setUp(): void
    {
        $this->cronConfigRepository = $this->createMock(CronConfigRepository::class);
        $this->schedule = new NewsImportSchedule($this->cronConfigRepository);
    }

    /**
     * Test getSchedule when config exists and is active.
     */
    public function testGetScheduleWithActiveConfig(): void
    {
        $config = $this->createMock(\App\Entity\CronConfig::class);
        $config->method('isActive')->willReturn(true);
        $config->method('getScheduleTime')->willReturn('08:00');

        $this->cronConfigRepository->method('findOneBy')->willReturn($config);

        $schedule = $this->schedule->getSchedule();

        $this->assertInstanceOf(\Symfony\Component\Scheduler\Schedule::class, $schedule);
        // Further assertions can be made on the schedule
    }

    /**
     * Test getSchedule when no config exists.
     */
    public function testGetScheduleWithoutConfig(): void
    {
        $this->cronConfigRepository->method('findOneBy')->willReturn(null);

        $schedule = $this->schedule->getSchedule();

        $this->assertInstanceOf(\Symfony\Component\Scheduler\Schedule::class, $schedule);
    }
}