<?php

namespace App\Scheduler;

use App\Message\NewsImportMessage;
use App\Repository\CronConfigRepository;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('news_import')]
/**
 * Schedule provider for news import tasks based on cron configuration.
 *
 * @author Mandy Vorköper <mandy-vor@gmx.de>
 */
final class NewsImportSchedule implements ScheduleProviderInterface
{
    /**
     * @param CronConfigRepository $cronConfigRepository Repository for cron configuration
     */
    public function __construct(
        private CronConfigRepository $cronConfigRepository
        
    ) {
    }

    /**
     * Provides the schedule for news import based on cron configuration.
     *
     * @return Schedule The schedule containing recurring news import messages
     */
    public function getSchedule(): Schedule
    {

        $config = $this->cronConfigRepository->findOneBy([]);
        $schedule = new Schedule();

        if ($config && $config->isActive()) {
            
            $scheduleTime = $config->getScheduleTime();
            if (!preg_match('/^\d{2}:\d{2}$/', $scheduleTime)) {
                // Invalid time format, skip scheduling
                return $schedule;
            }

            // Convert the time from DB (format "HH:MM") to cron format
            // Example: "14:30" becomes "30 14 * * *"
            [$hour, $minute] = explode(':', $scheduleTime);
            $hour = (int)$hour;
            $minute = (int)$minute;
            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                // Invalid hour or minute, skip scheduling
                return $schedule;
            }
            $cronExpression = sprintf('%d %d * * *', $minute, $hour);

            $schedule->add(
                RecurringMessage::cron($cronExpression, new NewsImportMessage())
            );
        }

       return $schedule;
    }
}
