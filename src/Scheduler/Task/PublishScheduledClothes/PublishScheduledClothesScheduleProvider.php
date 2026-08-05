<?php

namespace App\Scheduler\Task\PublishScheduledClothes;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('clothes_publication')]
final class PublishScheduledClothesScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())->add(RecurringMessage::every('1 minute', new PublishScheduledClothesMessage()));
    }
}
