<?php

namespace App\UI\Clothes\Category;

use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Application\Clothes\Provider\CategoryProvider\CategoryProvider;
use App\Entity\Category\Category;
use App\Entity\Collections\Collections;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final readonly class CategoryViewFactory
{
    private const COLUMNS = [
        ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
        ['key' => 'isOnline', 'label' => 'En ligne', 'sortable' => true, 'raw' => true],
        ['key' => 'collectionsCount', 'label' => 'Collections', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    public function __construct(
        private CategoryProvider $provider,
        private UrlGeneratorInterface $urls,
        private CsrfTokenManagerInterface $csrf,
        private Environment $twig,
    ) {
    }

    public function table(string $search, string $sort, string $direction): array
    {
        $search = trim($search);
        $allowedSorts = ['id', 'name', 'slug', 'isOnline', 'collectionsCount', 'createdAt'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'name';
        $direction = 'desc' === strtolower($direction) ? 'desc' : 'asc';

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(
                $this->row(...),
                $this->provider->search($search, $sort, $direction),
            ),
            'url' => $this->urls->generate('app_clothes_categories_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    public function onlineToggle(Category $category): string
    {
        $id = (int) $category->getId();
        $token = $this->csrf->getToken('category_online_' . $id)->getValue();
        $action = fn (string $state, string $label): array => (new ToggleActionModel(
            $this->urls->generate(
                'app_clothes_categories_toggle_online',
                ['id' => $id, 'state' => $state],
            ),
            $token,
            $label,
        ))->toArray();
        $toggle = new ToggleModel(
            'category-online-' . $id,
            $category->isOnline() ? 'En ligne' : 'Hors ligne',
            (bool) $category->isOnline(),
            'category_online_' . $id,
            ['on' => $action('on', 'En ligne'), 'off' => $action('off', 'Hors ligne')]
        );

        return $this->twig->render('ui/components/toggle/_toggle.html.twig', ['toggle' => $toggle->toArray()]);
    }

    public function collectionPublicationStates(Category $category, CollectionOnlineGuard $guard): array
    {
        $states = [];
        foreach ($category->getCollections() as $collection) {
            if (!$collection instanceof Collections || $collection->isOnline()) {
                continue;
            }
            $validation = $guard->canPublish($collection);
            $states[(int) $collection->getId()] = [
                'canPublish' => $validation->canPublish(),
                'errors' => $validation->getErrors(),
            ];
        }

        return $states;
    }

    private function row(array $result): array
    {
        $category = $result[0] ?? null;
        if (!$category instanceof Category) {
            return [];
        }
        $id = (int) $category->getId();

        return ['_rowId' => 'category-row-' . $id, 'id' => (string) $id, 'name' => (string) $category->getName(),
            'isOnline' => $this->onlineToggle($category),
            'collectionsCount' => (string) ($result['collectionsCount'] ?? 0),
            'actions' => $this->twig->render('clothes/categories/_actions.html.twig', ['categoryId' => $id,
                'showUrl' => $this->urls->generate('app_clothe_category_show', ['id' => $id]),
                'deleteUrl' => $this->urls->generate('app_clothe_category_delete', ['id' => $id]),
                'deleteToken' => $this->csrf->getToken('category_delete_' . $id)->getValue()])];
    }
}
