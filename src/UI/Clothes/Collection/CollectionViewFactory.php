<?php

namespace App\UI\Clothes\Collection;

use App\Application\Clothes\Provider\CollectionProvider\CollectionProvider;
use App\Entity\Collections\Collections;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final readonly class CollectionViewFactory
{
    private const COLUMNS = [
        ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
        ['key' => 'category', 'label' => 'Categorie', 'sortable' => true],
        ['key' => 'clothesCount', 'label' => 'Vetements', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = ['id', 'name', 'category', 'isOnline', 'clothesCount', 'createdAt'];

    public function __construct(
        private CollectionProvider $provider,
        private UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private Environment $twig,
    ) {
    }

    /** @return array<string, mixed> */
    public function table(string $search, string $sort, string $direction): array
    {
        $search = trim($search);
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'name';
        $direction = 'desc' === strtolower($direction) ? 'desc' : 'asc';

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map($this->mapRow(...), $this->provider->search($search, $sort, $direction)),
            'url' => $this->urlGenerator->generate('app_clothe_collection_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    public function onlineToggle(Collections $collection): string
    {
        $id = (int) $collection->getId();
        $csrfToken = $this->csrfTokenManager->getToken('collection_online_' . $id)->getValue();
        $action = fn (string $state, string $label): ToggleActionModel => new ToggleActionModel(
            url: $this->urlGenerator->generate('app_clothes_collection_toggle_online', ['id' => $id, 'state' => $state]),
            csrfToken: $csrfToken,
            label: $label,
        );
        $toggle = new ToggleModel(
            id: 'collection-online-' . $id,
            label: $collection->isOnline() ? 'En ligne' : 'Hors ligne',
            checked: (bool) $collection->isOnline(),
            name: 'collection_online_' . $id,
            payload: [
                'on' => $action('on', 'En ligne')->toArray(),
                'off' => $action('off', 'Hors ligne')->toArray(),
            ],
        );

        return $this->twig->render('ui/components/toggle/_toggle.html.twig', ['toggle' => $toggle->toArray()]);
    }

    /** @return array<string, mixed> */
    private function mapRow(array $result): array
    {
        $collection = $result[0] ?? null;
        if (!$collection instanceof Collections) {
            return [];
        }

        $id = (int) $collection->getId();

        return [
            '_rowId' => 'collection-row-' . $id,
            'name' => (string) $collection->getName(),
            'category' => (string) $collection->getCategory()?->getName(),
            'clothesCount' => (string) ($result['clothesCount'] ?? 0),
            'actions' => $this->twig->render('clothes/collections/_actions.html.twig', [
                'collectionId' => $id,
                'showUrl' => $this->urlGenerator->generate('app_clothes_collection', ['id' => $id]),
                'deleteUrl' => $this->urlGenerator->generate('app_clothe_collection_delete', ['id' => $id]),
                'deleteToken' => $this->csrfTokenManager->getToken('collection_delete_' . $id)->getValue(),
            ]),
        ];
    }
}
