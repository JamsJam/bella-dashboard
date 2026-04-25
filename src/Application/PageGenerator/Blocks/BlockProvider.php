<?php

namespace App\Application\PageGenerator\Blocks;

use App\Application\Clothes\Services\ClotheService;
use App\Application\PageGenerator\Blocks\Back\AdminSortableTableBlock;
use App\Application\PageGenerator\Blocks\Back\AdminTabsBlock;
use App\Application\PageGenerator\Services\PageService;
use App\Application\PageGenerator\Services\PaginateService;
use App\Entity\Clothes\Clothes;
use App\Service\ThemeService;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

final class BlockProvider
{
    

    public function __construct(
        private ThemeService $themeService,
        // private ServiceLocator $serviceLocator,

        #[AutowireLocator([
            'ClotheService' => ClotheService::class,
            'PaginateService' => PaginateService::class
        ])]
        private ContainerInterface $services
    ){}

    public function createBlock(array $config, array $params ): BlockInterface
    {
        switch ($config['type']) {
            case 'adminTabsBlock':
                
                return new AdminTabsBlock(
                    $config['tabs'] ?? [],
                    $this->themeService->getTheme(),
                    $config['reverse'] ?? false
                );

            case 'adminSortableTableBlock':
                //$params => {}

                // $serviceClass =  $this->serviceLocator->resolve($config['service']);
                $service = $this->services->get($config['service']);

                $block = new AdminSortableTableBlock(theme: $this->themeService->getTheme());



                $sortBy = $params['sortBy'] ?? null;
                $direction = $params['direction'] ?? null;
                $query = $params['query'] ?? null; 
                $limit = $config['limit'] ?? null; 
                $offset = $params['offset'] ?? null; 
                

                if (!$config['isPaginated'] && !$config['isSearchable'] && !$config['isSortable']) {
                    $dataRows = $service->{$config['methode']}($limit ) ;
                } else {
                    
                    
                    if ($config['isPaginated']) {
                        $totalItems = $service->getTotalItems();
                        $totalPage =  $this->services->get('PaginateService')->getTotalPage(count($totalItems),$limit);
                        $currentPage = $params['currentPage'] ?? 1;
                        $block->setCurrentPage($currentPage);
                        $offset = $limit * ($currentPage - 1 );
                    }
                    $dataRows = $service->{$config['methode']}($sortBy, $direction, $query, $limit, $offset ) ;
                    dump($dataRows);
                }
                
                






                
                $block->setRows($block->prepareRows($config['colTitles'], $dataRows) ?? [])
                    ->setIsPaginated($config['isPaginated'] ?? false)
                    ->setIsSortable($config['isSortable'] ?? false)
                    ->setReverse($config['reverse'] ?? false)
                    ->setColTitles($config['colTitles'] ?? [])
                    ->setMaxItems($config['maxItems'] ?? 20)
                    ->setNoItemsLabel($config['noItemsLabel'] ?? 'message tableau vide')
                    ->setTableTitle($config['tableTitle'] ?? 'Titre du tableau')

                    
                ;
                if ($config['isPaginated']) {
                    // paginatedService
                    // $maxPage = 0;
                    $block->setMaxPage($maxPage ?? null);
                    $block->setTotalPage($totalPage ?? null);
                    
                    ;
                }
                if ($config['isSortable']) {
                    $block->setCurrentSort( $params['sortBy'] ?? $config['defaultSort']);
                    $block->setCurrentDirection($params['direction'] ?? $config['defaultDirection']);
                }

                return $block;


            default:
                throw new \Exception("Bloc inconnu : {$config['type']}");
        }
    }
}
