<?php

namespace App\Scheduler\Task\DeleteUnconfirmedCustomers;

use App\Repository\Users\CustomersRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\Event\PostRunEvent;
use Symfony\Component\Scheduler\Event\PreRunEvent;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Trigger\CallbackMessageProvider;
use Symfony\Contracts\Cache\CacheInterface;


#[AsSchedule('default')]
final class DeleteUnconfirmedCustomersScheduleProvider implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        private EventDispatcherInterface $dispatcher,
        private CustomersRepository $customersRepository
    ) {
    }

    public function getSchedule(): Schedule
    {

        return $this->schedule ??= (new Schedule($this->dispatcher))
            ->with(
                RecurringMessage::every(
                    '5 minutes',
                    new CallbackMessageProvider([$this, 'getExpiredUnconfirmedCustomers']),
                )
                
            )
            // ->add(RecurringMessage::every(
            //     '5 minutes',
            //     new DeleteUnconfirmedCustomersMessage($expiredUnconfirmedCustomers),
            // ))
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            ->before(function(PreRunEvent $event) {
                $message = $event->getMessage();
                $customers = $message->getCustomers() ;
                if (empty($customers)) {
                    $event->ShouldCancel(true);
                }
            })

            ->onFailure(function(FailureEvent $event): void {
                $message = $event->getMessage();
                $error = $event->getError();

                // Log the error or perform any other necessary actions
                
            })

            ->after(function(PostRunEvent $event): void {
                $message = $event->getMessage();
                $result = $event->getResult();

                // Perform any necessary actions after the task has run
                //logging, notifications, etc.

            })
            


        ;
    }

    public function getExpiredUnconfirmedCustomers(MessageContext $context ): iterable {
        $customers = $this->customersRepository
            ->findExpiredUnconfirmedCustomers();

        yield new DeleteUnconfirmedCustomersMessage($customers);


        }
    }
