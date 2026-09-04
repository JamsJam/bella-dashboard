<?php

namespace App\Controller\Orders;

use App\Application\Orders\Delivery\MarkOrderDeliveredService;
use App\Application\Orders\Workflow\OrderWorkflow;
use App\Entity\Orders\Orders;
use App\Enum\OrderStatus;
use App\Repository\Orders\OrdersRepository;
use App\Service\BreadscrumbsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

final class DeliveriesController extends AbstractController
{
    private const TIMEZONE = 'Europe/Paris';
    private const INITIAL_DAYS_BEFORE = 3;
    private const INITIAL_DAYS_AFTER = 5;
    private const LOAD_MORE_DAYS = 3;

    #[Route('/deliveries', name: 'app_orders_deliveries', methods: ['GET'])]
    public function index(Request $request, OrdersRepository $ordersRepository, BreadscrumbsService $breadscrumbs, ClockInterface $clock): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $today = $this->today($clock);
        $days = $this->createDays(
            $today->modify(sprintf('-%d days', self::INITIAL_DAYS_BEFORE)),
            self::INITIAL_DAYS_BEFORE + self::INITIAL_DAYS_AFTER + 1,
            $today,
            $ordersRepository,
        );

        return $this->render('deliveries/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'days' => $days,
            'startDate' => $days[0]['date']->format('Y-m-d'),
            'endDate' => $days[array_key_last($days)]['date']->format('Y-m-d'),
        ]);
    }

    #[Route('/deliveries/days', name: 'app_orders_deliveries_days', methods: ['GET'])]
    public function days(Request $request, OrdersRepository $ordersRepository, ClockInterface $clock): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $rawBoundary = (string) $request->query->get('boundary');
        $boundary = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $rawBoundary,
            new \DateTimeZone(self::TIMEZONE),
        );
        $direction = (string) $request->query->get('direction');

        if (
            !$boundary instanceof \DateTimeImmutable
            || $boundary->format('Y-m-d') !== $rawBoundary
            || !in_array($direction, ['before', 'after'], true)
        ) {
            return $this->json(['message' => 'Paramètres de période invalides.'], Response::HTTP_BAD_REQUEST);
        }

        $start = 'before' === $direction
            ? $boundary->modify(sprintf('-%d days', self::LOAD_MORE_DAYS))
            : $boundary->modify('+1 day');
        $days = $this->createDays($start, self::LOAD_MORE_DAYS, $this->today($clock), $ordersRepository);

        return $this->json([
            'html' => $this->renderView('deliveries/_days.html.twig', ['days' => $days]),
            'startDate' => $days[0]['date']->format('Y-m-d'),
            'endDate' => $days[array_key_last($days)]['date']->format('Y-m-d'),
        ]);
    }

    #[Route('/deliveries/orders/{id}/delivered/modal', name: 'app_orders_deliveries_delivered_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function deliveredModal(int $id, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!in_array($order->getOrderStatus(), [OrderStatus::AwaitingDelivery, OrderStatus::Shipped], true)) {
            throw $this->createAccessDeniedException('Cette commande ne peut pas être marquée comme livrée.');
        }

        $html = $this->renderView('deliveries/_delivered_modal.html.twig', ['order' => $order]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/deliveries/orders/{id}/delivered', name: 'app_orders_deliveries_mark_delivered', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markDelivered(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MarkOrderDeliveredService $markOrderDelivered,
        #[Target(OrderWorkflow::NAME)]
        WorkflowInterface $orderWorkflow,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('mark_order_delivered_' . $id, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $blockers = $orderWorkflow->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_MARK_DELIVERED);
        if (!$blockers->isEmpty()) {
            foreach ($blockers as $blocker) {
                $this->addFlash('error', $blocker->getMessage());
            }

            return $this->redirectToRoute('app_orders_deliveries');
        }

        $markOrderDelivered->mark($order);

        $this->addFlash('success', sprintf('La commande %s a été marquée comme livrée.', $order->getOrderReference()));

        return $this->redirectToRoute('app_orders_deliveries');
    }

    /**
     * @return list<array{date: \DateTimeImmutable, label: string, isToday: bool, orders: list<Orders>}>
     */
    private function createDays(\DateTimeImmutable $start, int $numberOfDays, \DateTimeImmutable $today, OrdersRepository $ordersRepository): array
    {
        $end = $start->modify(sprintf('+%d days', $numberOfDays - 1));
        $ordersByDate = [];

        foreach ($ordersRepository->findPaidAwaitingDeliveryBetween($start, $end) as $order) {
            $date = ($order->getDeliveryDate() ?? $order->getDeliveredAt())?->format('Y-m-d');
            if (null !== $date) {
                $ordersByDate[$date][] = $order;
            }
        }

        $days = [];
        for ($offset = 0; $offset < $numberOfDays; ++$offset) {
            $date = $start->modify(sprintf('+%d days', $offset));
            $dateKey = $date->format('Y-m-d');
            $days[] = [
                'date' => $date,
                'label' => $this->dayLabel($date),
                'isToday' => $dateKey === $today->format('Y-m-d'),
                'orders' => $ordersByDate[$dateKey] ?? [],
            ];
        }

        return $days;
    }

    private function today(ClockInterface $clock): \DateTimeImmutable
    {
        return $clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->setTime(0, 0);
    }

    private function dayLabel(\DateTimeImmutable $date): string
    {
        $weekdays = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $months = [1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'];

        return sprintf('%s %d %s', $weekdays[(int) $date->format('w')], (int) $date->format('j'), $months[(int) $date->format('n')]);
    }
}
