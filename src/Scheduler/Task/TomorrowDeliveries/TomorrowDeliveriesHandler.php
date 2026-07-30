<?php

namespace App\Scheduler\Task\TomorrowDeliveries;

use App\Notifier\Services\EmailNotificationService;
use App\Repository\Orders\OrdersRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TomorrowDeliveriesHandler
{
    private const TIMEZONE = 'Europe/Paris';

    public function __construct(
        private OrdersRepository $ordersRepository,
        private TomorrowDeliveriesCsvBuilder $csvBuilder,
        private EmailNotificationService $emailNotificationService,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(TomorrowDeliveriesMessage $message): void
    {
        $deliveryDate = $this->clock
            ->now()
            ->setTimezone(new \DateTimeZone(self::TIMEZONE))
            ->setTime(0, 0)
            ->modify('+1 day');
        $orders = $this->ordersRepository->findPaidAwaitingDeliveryOn($deliveryDate);

        if ($orders === []) {
            return;
        }

        $filename = sprintf('livraisons-%s.csv', $deliveryDate->format('Y-m-d'));

        $this->emailNotificationService->sendTemplatedAdminEmailWithAttachment(
            subject: sprintf('Livraisons prévues le %s', $deliveryDate->format('d/m/Y')),
            template: 'email/tomorrow_deliveries_owner.html.twig',
            context: [
                'deliveryDate' => $deliveryDate,
                'ordersCount' => count($orders),
            ],
            attachment: $this->csvBuilder->build($orders),
            filename: $filename,
            contentType: 'text/csv',
        );
    }
}
