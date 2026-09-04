<?php

namespace App\Controller\Orders;

use App\Application\Config\Provider\OrdersConfigProvider;
use App\Application\Orders\Delivery\AutoDeliverShippedOrderMessage;
use App\Application\Orders\Delivery\DeliveryDatePolicy;
use App\Application\Orders\Delivery\DeliveryReminderMessage;
use App\Application\Orders\Dto\ShipmentDto;
use App\Application\Orders\Form\ShipmentType;
use App\Application\Orders\Mapper\OrderStatusSortMapper;
use App\Application\Orders\Workflow\OrderWorkflow;
use App\Entity\Orders\Orders;
use App\Enum\OrderStatus;
use App\Notifier\Services\EmailNotificationService;
use App\Service\BreadscrumbsService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

final class OrdersController extends AbstractController
{
    private const PAGE_SIZE = 20;

    private const PAYMENT_STATUS_LABELS = [
        Orders::STATUS_PENDING_PAYMENT => 'En attente',
        Orders::STATUS_PAID => 'Payée',
        Orders::STATUS_PAYMENT_EXPIRED => 'Expiré',
        Orders::STATUS_CHECKOUT_CREATION_FAILED => 'Échec checkout',
    ];

    private const COLUMNS = [
        ['key' => 'orderReference', 'label' => 'Commande', 'sortable' => true, 'raw' => true],
        ['key' => 'createdAt', 'label' => 'Date', 'sortable' => true, 'raw' => true],
        ['key' => 'payment', 'label' => 'Paiement', 'sortable' => true, 'raw' => true],
        ['key' => 'status', 'label' => 'Traitement', 'sortable' => true, 'raw' => true],
        ['key' => 'total', 'label' => 'Montant', 'sortable' => true, 'raw' => true],
        ['key' => 'delivery', 'label' => 'Livraison', 'sortable' => false, 'raw' => true],
        ['key' => 'reviews', 'label' => 'Avis', 'sortable' => false, 'raw' => true],
        ['key' => 'actions', 'label' => 'Action', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = [
        'orderReference' => 'o.orderReference',
        'total' => 'o.total',
        'payment' => 'o.status',
        'status' => 'o.orderStatus',
        'createdAt' => 'o.createdAt',
    ];

    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(Request $request, BreadscrumbsService $breadscrumbs, EntityManagerInterface $entityManager, DeliveryDatePolicy $deliveryDatePolicy, OrderStatusSortMapper $statusSortMapper): Response
    {
        return $this->render('orders/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'table' => $this->createTableData($request, $entityManager, $deliveryDatePolicy, $statusSortMapper),
            'summary' => $this->createSummary($entityManager),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    #[Route('/orders/{id}', name: 'app_orders_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, Request $request, EntityManagerInterface $entityManager, BreadscrumbsService $breadscrumbs, DeliveryDatePolicy $deliveryDatePolicy): Response
    {
        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $reviewSummaries = $this->reviewSummaries([$order], $entityManager);

        return $this->render('orders/show.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                route: (string) $request->attributes->get('_route'),
                routeParams: ['id' => $id],
                currentLabel: $order->getOrderReference() ?? sprintf('Commande #%d', $id),
            ),
            'order' => $order,
            'minimumDeliveryDate' => $deliveryDatePolicy->minimumDeliveryDate(),
            'reviewSummary' => $reviewSummaries[$id] ?? ['total' => 0, 'average' => null, 'reviewUuids' => []],
        ]);
    }

    #[Route('/orders/table', name: 'app_orders_table', methods: ['GET'])]
    public function table(Request $request, EntityManagerInterface $entityManager, DeliveryDatePolicy $deliveryDatePolicy, OrderStatusSortMapper $statusSortMapper): JsonResponse
    {
        $table = $this->createTableData($request, $entityManager, $deliveryDatePolicy, $statusSortMapper);

        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]),
            'pagination' => $this->renderView('ui/components/data-table/_pagination.html.twig', [
                'pagination' => $table['pagination'],
            ]),
            'page' => $table['pagination']['page'],
        ]);
    }

    #[Route('/orders/{id}/delivery-date/modal', name: 'app_orders_delivery_date_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function deliveryDateModal(int $id, EntityManagerInterface $entityManager, DeliveryDatePolicy $deliveryDatePolicy): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (OrderStatus::Processing !== $order->getOrderStatus() || !$order->isShippingToGuadeloupe()) {
            throw $this->createAccessDeniedException('Cette commande ne peut pas être programmée pour une livraison.');
        }

        $html = $this->renderView('orders/_delivery_date_modal.html.twig', [
            'order' => $order,
            'minimumDate' => $deliveryDatePolicy->minimumDeliveryDate(),
            'calendar' => $this->createDeliveryCalendar($deliveryDatePolicy->minimumDeliveryDate()),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/orders/{id}/delivery-date', name: 'app_orders_delivery_date', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function scheduleDelivery(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        DeliveryDatePolicy $deliveryDatePolicy,
        MessageBusInterface $messageBus,
        #[Target(OrderWorkflow::NAME)]
        WorkflowInterface $orderWorkflow,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'order_delivery_date_' . $id,
                (string) $request->request->get('_csrf_token'),
            )
        ) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $rawDate = (string) $request->request->get('delivery_date');
        $deliveryDate = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $rawDate,
            new \DateTimeZone(DeliveryDatePolicy::TIMEZONE),
        );

        if (!$deliveryDate instanceof \DateTimeImmutable || $deliveryDate->format('Y-m-d') !== $rawDate) {
            $this->addFlash('error', 'La date de livraison est invalide.');

            return $this->redirectToRoute('app_orders');
        }

        $order->setDeliveryDate($deliveryDate);
        $blockers = $orderWorkflow->buildTransitionBlockerList(
            $order,
            OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY,
        );

        if (!$blockers->isEmpty()) {
            foreach ($blockers as $blocker) {
                $this->addFlash('error', $blocker->getMessage());
            }

            return $this->redirectToRoute('app_orders');
        }

        $orderWorkflow->apply($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY);
        $entityManager->flush();

        $messageBus->dispatch(
            new DeliveryReminderMessage((int) $order->getId(), $deliveryDate->format('Y-m-d')),
            [new DelayStamp($deliveryDatePolicy->reminderDelayMilliseconds($deliveryDate))],
        );

        $this->addFlash(
            'success',
            sprintf('Livraison de la commande %s prévue le %s.', $order->getOrderReference(), $deliveryDate->format('d/m/Y')),
        );

        return $this->redirectToRoute('app_orders');
    }

    #[Route('/orders/{id}/shipment/modal', name: 'app_orders_shipment_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function shipmentModal(int $id, EntityManagerInterface $entityManager, OrdersConfigProvider $ordersConfigProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (OrderStatus::Processing !== $order->getOrderStatus() || !$order->isShippingOutsideGuadeloupe()) {
            throw $this->createAccessDeniedException('Cette commande ne peut pas être expédiée.');
        }

        $carriers = $ordersConfigProvider->get()->carriers;
        $form = $this->createForm(ShipmentType::class, new ShipmentDto(), [
            'action' => $this->generateUrl('app_orders_shipment', ['id' => $id]),
            'carriers' => $carriers,
            'csrf_token_id' => 'ship_order_' . $id,
        ]);
        $html = $this->renderView('orders/_shipment_modal.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/orders/{id}/shipment', name: 'app_orders_shipment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ship(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        EmailNotificationService $emailNotificationService,
        OrdersConfigProvider $ordersConfigProvider,
        #[Target(OrderWorkflow::NAME)]
        WorkflowInterface $orderWorkflow,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $entityManager->find(Orders::class, $id);
        if (!$order instanceof Orders) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $carriers = $ordersConfigProvider->get()->carriers;
        $shipment = new ShipmentDto();
        $form = $this->createForm(ShipmentType::class, $shipment, [
            'carriers' => $carriers,
            'csrf_token_id' => 'ship_order_' . $id,
        ]);
        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Le numéro d’expédition et le transporteur sont obligatoires.');

            return $this->redirectToRoute('app_orders');
        }

        $carrier = $carriers[(int) $shipment->carrier] ?? null;
        if (null === $carrier) {
            $this->addFlash('error', 'Le transporteur sélectionné n’existe plus dans la configuration.');

            return $this->redirectToRoute('app_orders');
        }

        $trackingNumber = trim($shipment->trackingNumber);
        $order
            ->setTrackingNumber($trackingNumber)
            ->setCarrierName($carrier->name)
            ->setCarrierTrackingUrl($carrier->trackingUrl);
        $blockers = $orderWorkflow->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_SHIP);
        if (!$blockers->isEmpty()) {
            foreach ($blockers as $blocker) {
                $this->addFlash('error', $blocker->getMessage());
            }

            return $this->redirectToRoute('app_orders');
        }

        $shippedAt = new \DateTimeImmutable('now', new \DateTimeZone(DeliveryDatePolicy::TIMEZONE));
        $order->setShippedAt($shippedAt);
        $orderWorkflow->apply($order, OrderWorkflow::TRANSITION_SHIP);
        $entityManager->flush();

        $messageBus->dispatch(
            new AutoDeliverShippedOrderMessage((int) $order->getId(), $trackingNumber),
            [new DelayStamp(20 * 24 * 60 * 60 * 1000)],
        );

        $customerEmail = $order->getCustomer()?->getEmail();
        if (null !== $customerEmail && '' !== $customerEmail) {
            $emailNotificationService->sendTemplatedEmail(
                to: $customerEmail,
                subject: sprintf('Votre commande %s a été expédiée', (string) $order->getOrderReference()),
                template: 'email/order_shipped_customer.html.twig',
                context: ['order' => $order],
            );
        }

        $this->addFlash('success', sprintf('La commande %s a été expédiée.', $order->getOrderReference()));

        return $this->redirectToRoute('app_orders');
    }

    private function createTableData(Request $request, EntityManagerInterface $entityManager, DeliveryDatePolicy $deliveryDatePolicy, OrderStatusSortMapper $statusSortMapper): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $status = OrderStatus::tryFrom((string) $request->query->get('status', ''));
        $paymentStatus = $this->normalizePaymentStatus((string) $request->query->get('paymentStatus', ''));
        $destination = $this->normalizeDestination((string) $request->query->get('destination', ''));
        $scheduling = (string) $request->query->get('scheduling', '');
        $sort = $this->normalizeSort((string) $request->query->get('sort', 'createdAt'));
        $direction = $this->normalizeDirection((string) $request->query->get('direction', 'desc'));
        $requestedPage = max(1, $request->query->getInt('page', 1));

        $queryBuilder = $entityManager->getRepository(Orders::class)
            ->createQueryBuilder('o')
            ->addSelect('customer')
            ->leftJoin('o.customer', 'customer');

        if ('status' === $sort) {
            $queryBuilder
                ->addSelect(sprintf('(%s) AS HIDDEN orderStatusRank', $statusSortMapper->dqlCase('o.orderStatus')))
                ->orderBy('orderStatusRank', $direction);
        } else {
            $queryBuilder->orderBy(self::SORTS[$sort], $direction);
        }

        $queryBuilder->addOrderBy('o.createdAt', 'DESC');

        if ('' !== $search) {
            $queryBuilder
                ->andWhere('LOWER(o.orderReference) LIKE :search OR LOWER(o.orderStatus) LIKE :search OR LOWER(customer.email) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($status instanceof OrderStatus) {
            $queryBuilder
                ->andWhere('o.orderStatus = :orderStatus')
                ->setParameter('orderStatus', $status);
        }

        if (null !== $paymentStatus) {
            $queryBuilder
                ->andWhere('o.status = :paymentStatus')
                ->setParameter('paymentStatus', $paymentStatus);
        }

        if ('guadeloupe' === $destination) {
            $queryBuilder
                ->andWhere('LOWER(o.shippinfo) LIKE :destination')
                ->setParameter('destination', '%guadeloupe%');
        } elseif ('other' === $destination) {
            $queryBuilder
                ->andWhere('LOWER(o.shippinfo) NOT LIKE :destination')
                ->setParameter('destination', '%guadeloupe%');
        }

        if ('to_schedule' === $scheduling) {
            $queryBuilder
                ->andWhere('o.orderStatus = :schedulingStatus')
                ->andWhere('o.deliveryDate IS NULL')
                ->andWhere('LOWER(o.shippinfo) LIKE :guadeloupe')
                ->setParameter('schedulingStatus', OrderStatus::Processing)
                ->setParameter('guadeloupe', '%guadeloupe%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->select('COUNT(DISTINCT o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalItems / self::PAGE_SIZE));
        $page = min($requestedPage, $totalPages);

        $orders = $queryBuilder
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();
        $reviewSummaries = $this->reviewSummaries($orders, $entityManager);

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(
                fn (Orders $order): array => $this->mapOrder(
                    $order,
                    $deliveryDatePolicy,
                    $reviewSummaries[(int) $order->getId()] ?? ['total' => 0, 'average' => null],
                ),
                $orders,
            ),
            'url' => $this->generateUrl('app_orders_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
            'searchPlaceholder' => 'Référence ou e-mail client',
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'pages' => range(max(1, $page - 2), min($totalPages, $page + 2)),
            ],
            'filters' => [[
                'name' => 'status',
                'label' => 'Statut de commande',
                'value' => $status?->value ?? '',
                'options' => [
                    ['value' => '', 'label' => 'Tous les états'],
                    ...array_map(
                        static fn (OrderStatus $orderStatus): array => [
                            'value' => $orderStatus->value,
                            'label' => $orderStatus->label(),
                        ],
                        OrderStatus::cases(),
                    ),
                ],
            ], [
                'name' => 'paymentStatus',
                'label' => 'Statut de paiement',
                'value' => $paymentStatus ?? '',
                'options' => [
                    ['value' => '', 'label' => 'Tous les paiements'],
                    ...array_map(
                        static fn (string $value, string $label): array => [
                            'value' => $value,
                            'label' => $label,
                        ],
                        array_keys(self::PAYMENT_STATUS_LABELS),
                        array_values(self::PAYMENT_STATUS_LABELS),
                    ),
                ],
            ], [
                'name' => 'destination',
                'label' => 'Destination',
                'value' => $destination ?? '',
                'options' => [
                    ['value' => '', 'label' => 'Toutes les destinations'],
                    ['value' => 'guadeloupe', 'label' => 'Guadeloupe'],
                    ['value' => 'other', 'label' => 'Autres destinations'],
                ],
            ], [
                'name' => 'scheduling',
                'label' => 'Programmation',
                'value' => 'to_schedule' === $scheduling ? $scheduling : '',
                'options' => [
                    ['value' => '', 'label' => 'Toutes les commandes'],
                    ['value' => 'to_schedule', 'label' => 'À programmer'],
                ],
            ]],
        ];
    }

    /** @param array{total: int, average: float|null, reviewUuids?: list<string>} $reviewSummary */
    private function mapOrder(Orders $order, DeliveryDatePolicy $deliveryDatePolicy, array $reviewSummary): array
    {
        return [
            'orderReference' => $this->renderView('orders/_order_cell.html.twig', ['order' => $order]),
            'createdAt' => $this->renderView('orders/_date_cell.html.twig', ['order' => $order]),
            'payment' => $this->renderView('orders/_payment_badge.html.twig', ['order' => $order]),
            'status' => $this->renderView('orders/_status_badge.html.twig', ['order' => $order]),
            'total' => $this->renderView('orders/_amount_cell.html.twig', ['order' => $order]),
            'delivery' => $this->renderView('orders/_delivery_cell.html.twig', [
                'order' => $order,
                'minimumDate' => $deliveryDatePolicy->minimumDeliveryDate(),
            ]),
            'reviews' => $this->renderView('orders/_reviews_cell.html.twig', [
                'order' => $order,
                'reviewSummary' => $reviewSummary,
            ]),
            'actions' => $this->renderView('orders/_table_actions.html.twig', ['order' => $order]),
        ];
    }

    /**
     * @param list<Orders> $orders
     *
     * @return array<int, array{total: int, average: float|null, reviewUuids: list<string>}>
     */
    private function reviewSummaries(array $orders, EntityManagerInterface $entityManager): array
    {
        $orderIds = array_values(array_filter(array_map(
            static fn (Orders $order): ?int => $order->getId(),
            $orders,
        )));
        if ([] === $orderIds) {
            return [];
        }

        $rows = $entityManager->getConnection()
            ->createQueryBuilder()
            ->select('review.order_id', 'review.review_uuid', 'review.rating')
            ->from('review', 'review')
            ->where('review.order_id IN (:orderIds)')
            ->setParameter('orderIds', $orderIds, ArrayParameterType::INTEGER)
            ->orderBy('review.id', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $summaries = [];
        foreach ($rows as $row) {
            $orderId = (int) $row['order_id'];
            $summaries[$orderId] ??= [
                'total' => 0,
                'average' => null,
                'reviewUuids' => [],
                '_ratingTotal' => 0,
                '_ratingCount' => 0,
            ];
            ++$summaries[$orderId]['total'];
            $summaries[$orderId]['reviewUuids'][] = (string) $row['review_uuid'];

            if (null !== $row['rating']) {
                $summaries[$orderId]['_ratingTotal'] += (int) $row['rating'];
                ++$summaries[$orderId]['_ratingCount'];
            }
        }

        foreach ($summaries as &$summary) {
            if ($summary['_ratingCount'] > 0) {
                $summary['average'] = round(
                    $summary['_ratingTotal'] / $summary['_ratingCount'],
                    1,
                );
            }
            unset($summary['_ratingTotal'], $summary['_ratingCount']);
        }
        unset($summary);

        return $summaries;
    }

    /**
     * @return array{total: int, revenue: int, statuses: array<string, int>}
     */
    private function createSummary(EntityManagerInterface $entityManager): array
    {
        $statuses = array_fill_keys(
            array_map(static fn (OrderStatus $status): string => $status->value, OrderStatus::cases()),
            0,
        );

        $rows = $entityManager->getRepository(Orders::class)
            ->createQueryBuilder('o')
            ->select('o.orderStatus AS status, COUNT(o.id) AS count')
            ->groupBy('o.orderStatus')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $status = $row['status'] instanceof OrderStatus ? $row['status']->value : (string) $row['status'];
            if (array_key_exists($status, $statuses)) {
                $statuses[$status] = (int) $row['count'];
            }
        }

        $total = $entityManager->getRepository(Orders::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $revenue = $entityManager->getRepository(Orders::class)
            ->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.status = :paid')
            ->setParameter('paid', Orders::STATUS_PAID)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'revenue' => (int) $revenue,
            'statuses' => $statuses,
        ];
    }

    private function normalizeSort(string $sort): string
    {
        return array_key_exists($sort, self::SORTS) ? $sort : 'createdAt';
    }

    private function normalizeDirection(string $direction): string
    {
        return 'asc' === strtolower($direction) ? 'asc' : 'desc';
    }

    private function normalizePaymentStatus(string $status): ?string
    {
        return array_key_exists($status, self::PAYMENT_STATUS_LABELS) ? $status : null;
    }

    private function normalizeDestination(string $destination): ?string
    {
        return in_array($destination, ['guadeloupe', 'other'], true) ? $destination : null;
    }

    /**
     * @return array{label: string, weekdays: list<string>, days: list<array{
     *     date: string,
     *     day: int,
     *     currentMonth: bool,
     *     available: bool
     * }>}
     */
    private function createDeliveryCalendar(\DateTimeImmutable $minimumDate): array
    {
        $monthNames = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];
        $monthStart = $minimumDate->modify('first day of this month');
        $gridStart = $monthStart->modify(sprintf('-%d days', (int) $monthStart->format('N') - 1));
        $days = [];

        for ($offset = 0; $offset < 42; ++$offset) {
            $date = $gridStart->modify(sprintf('+%d days', $offset));
            $currentMonth = $date->format('Y-m') === $monthStart->format('Y-m');

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => (int) $date->format('j'),
                'currentMonth' => $currentMonth,
                'available' => $currentMonth && $date >= $minimumDate,
            ];
        }

        return [
            'label' => sprintf('%s %s', $monthNames[(int) $monthStart->format('n')], $monthStart->format('Y')),
            'weekdays' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            'days' => $days,
        ];
    }

    private function formatPrice(int $amount): string
    {
        return number_format($amount / 100, 2, ',', ' ') . ' EUR';
    }
}
