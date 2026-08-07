<?php

namespace App\Scheduler\Task\CleanupImageDeformations;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('image_deformation_cleanup')]
final class CleanupImageDeformationsScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(RecurringMessage::every(
            '1 day',
            new CleanupImageDeformationsMessage(),
            new \DateTimeImmutable('tomorrow 06:00', new \DateTimeZone('Europe/Paris')),
        ));
    }
}
