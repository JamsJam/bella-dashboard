<?php

namespace App\Application\PageGenerator\Blocks\Back;

use App\Application\PageGenerator\Blocks\BlockInterface;
// use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminSortableTableBlock implements BlockInterface
{
    private array $rows = [];
    private bool $isPaginated = false;
    private bool $reverse = false;
    private array $colTitles = [];
    private int $maxItems = 20;
    private string $noItemsLabel = 'message tableau vide';
    private string $tableTitle = 'Titre du tableau';
    private ?int $maxPage = null;
    private ?string $currentSort = null;
    private ?int $currentPage = null;
    private ?int $totalPage = null;
    private ?string $currentDirection = null;
    private bool $isSortable = false;

    public function __construct(
        private string $theme
    ){}

    public function getType(): string
    {
        return 'adminSortableTableBlock';
    }

    public function getData(): array
    {
        return [
            'theme' => $this->theme,
            'rows' => $this->rows,
            'reverse' => $this->reverse,
            'isPaginated' => $this->isPaginated,
            'isSortable' => $this->isSortable,
            'maxItems' => $this->maxItems,
            'tableTitle' => $this->tableTitle,
            'colTitles' => $this->colTitles,
            'noItemsLabel' => $this->noItemsLabel,
            'currentSort' => $this->currentSort,
            'currentPage' => $this->currentPage,
            'currentDirection' => $this->currentDirection,
            'maxPage' => $this->maxPage,
            'totalPage' => $this->totalPage,
        ];
    }

    public function getTemplate(): string
    {
        return 'partials/blocks/admin/AdminSortableTableBlock.html.twig';
    }

    public function prepareRows(array $colTitles, array $data): array
    {
        $rows = [];

        foreach ($data as $item) {
            $row = [];

            foreach ($colTitles as $col) {
                $type = $col['dataType'] ?? 'text';
                $property = $col['property'] ?? null;

                switch ($type) {
                    case 'text':
                        $row[] = [
                            'type' => $type,
                            'value' => $item[$property] ?? null,
                        ];
                        break;
                    case 'image':
                        $row[] = [
                            'type' => $type,
                            'value' => $item[$property] ?? null,
                        ];
                        break;

                    case 'boolean':
                        $value = (bool)($item[$property] ?? false);
                        $row[] = [
                            'type' => 'boolean',
                            'value' => $value,
                            'onTrue' => $col['isTrue'] ?? '✔',
                            'onFalse' => $col['isFalse'] ?? '✘',
                        ];
                        break;

                    case 'action':
                        $actions = [];
                        foreach ($col['options'] as $action) {
                            $paramName = $action['param'] ?? 'id';
                            $paramValue = $item[$paramName] ?? null;

                            $url = $action['route'];
                            $params = [$paramName => $paramValue];
                            

                            $actions[] = [
                                'type'  => $action['type'],
                                'label' => $action['label'],
                                'url'   => $url,
                                'params'   => $params,
                            ];
                        }
                        $row[] = [
                            'type' => 'action',
                            'value' => $actions,
                        ];
                        break;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }





    // ===== theme =====
    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    // ===== rows =====
    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    // ===== isPaginated =====
    public function getIsPaginated(): bool
    {
        return $this->isPaginated;
    }

    public function setIsPaginated(bool $isPaginated): static
    {
        $this->isPaginated = $isPaginated;
        return $this;
    }
    public function getTotalPage(): bool
    {
        return $this->totalPage;
    }

    public function setTotalPage(bool $totalPage): static
    {
        $this->totalPage = $totalPage;
        return $this;
    }

    // ===== reverse =====
    public function getReverse(): bool
    {
        return $this->reverse;
    }

    public function setReverse(bool $reverse): static
    {
        $this->reverse = $reverse;
        return $this;
    }

    // ===== colTitles =====
    public function getColTitles(): array
    {
        return $this->colTitles;
    }

    public function setColTitles(array $colTitles): static
    {
        $this->colTitles = $colTitles;
        return $this;
    }

    // ===== maxItems =====
    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    public function setMaxItems(int $maxItems): static
    {
        $this->maxItems = $maxItems;
        return $this;
    }

    // ===== noItemsLabel =====
    public function getNoItemsLabel(): string
    {
        return $this->noItemsLabel;
    }

    public function setNoItemsLabel(string $noItemsLabel): static
    {
        $this->noItemsLabel = $noItemsLabel;
        return $this;
    }

    // ===== tableTitle =====
    public function getTableTitle(): string
    {
        return $this->tableTitle;
    }

    public function setTableTitle(string $tableTitle): static
    {
        $this->tableTitle = $tableTitle;
        return $this;
    }

    // ===== maxPage =====
    public function getMaxPage(): ?int
    {
        return $this->maxPage;
    }

    public function setMaxPage(?int $maxPage): static
    {
        $this->maxPage = $maxPage;
        return $this;
    }

    /**
     * Get the value of currentDirection
     */ 
    public function getCurrentDirection(): ?string
    {
        return $this->currentDirection;
    }

    /**
     * Set the value of currentDirection
     *
     * @return  self
     */ 
    public function setCurrentDirection(?string $currentDirection): static
    {
        $this->currentDirection = $currentDirection;

        return $this;
    }

    /**
     * Get the value of currentSort
     */ 
    public function getCurrentSort(): ?string
    {
        return $this->currentSort;
    }

    /**
     * Set the value of currentSort
     *
     * @return  self
     */ 
    public function setCurrentSort(?string $currentSort): static
    {
        $this->currentSort = $currentSort;

        return $this;
    }

    /**
     * Get the value of isSortable
     */ 
    public function getIsSortable(): bool
    {
        return $this->isSortable;
    }

    /**
     * Set the value of isSortable
     *
     * @return  self
     */ 
    public function setIsSortable($isSortable): static
    {
        $this->isSortable = $isSortable;

        return $this;
    }

    /**
     * Get the value of currentPage
     */ 
    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }

    /**
     * Set the value of currentPage
     *
     * @return  self
     */ 
    public function setCurrentPage($currentPage): static
    {
        $this->currentPage = $currentPage;

        return $this;
    }
}
