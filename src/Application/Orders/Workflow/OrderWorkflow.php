<?php

namespace App\Application\Orders\Workflow;

final class OrderWorkflow
{
    public const NAME = 'order';
    public const TRANSITION_PROCESS = 'process';
    public const TRANSITION_CANCEL = 'cancel';
    public const TRANSITION_SCHEDULE_DELIVERY = 'schedule_delivery';
    public const TRANSITION_SHIP = 'ship';
    public const TRANSITION_MARK_DELIVERED = 'mark_delivered';

    private function __construct()
    {
    }
}
