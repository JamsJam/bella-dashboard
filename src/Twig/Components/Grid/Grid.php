<?php

namespace App\Twig\Components\Grid;

use App\Resolver\Avatar\BodyPartRegistryResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Grid
{
    use DefaultActionTrait;
    
    public function __construct(
        private BodyPartRegistryResolver $bodyPartRegistryResolver,
        private EntityManagerInterface $entityManager,
    ){}

    //? ======== body part
    public string $part = '';

    //? ======== Entity
    public ?string $itemsEntity = null;
    
    //? ======== filter Entity
    public array $filterEntity = [];

    //? ======== filter stock ids of filter
    public ?array $colorFilter = null;
    
    public ?array $shapeFilter = null;
    
    public ?array $skincolorFilter = null;
    
    public ?array $morphologieFilter = null;
    
    public ?array $morphotypeFilter = null;
    
    public ?array $clothesFilter = null;
    
    public ?array $collectionFilter = null;

    //? ===========

    //item a afficher
    public ?array $items = [];
    
    //item de filtre
    public ?array $filterItems = [];



    
    public function mount(string $part){
        $this->part = $part;
        $this->getItemEntity($this->part);
        $this->getFiltersEntity($this->part);
        $this->initFilterProperties();
        $this->filterItems(
            $this->itemsEntity,
            $this->colorFilter       ,
            $this->skincolorFilter   ,
            $this->shapeFilter       ,
            $this->morphologieFilter ,
            $this->morphotypeFilter  ,
            $this->clothesFilter     ,
            $this->collectionFilter   ,
        );
        $this->fetchFilterItem($this->filterEntity);
    }

    public function getItemEntity($bodyPart):void
    {
        $itemsEntity = $this->bodyPartRegistryResolver->getEntity('body',$bodyPart);
        $this->itemsEntity = $itemsEntity;
    }

    public function getFiltersEntity($bodyPart):void
    {
        $filterEntity = $this->bodyPartRegistryResolver->getFilters($bodyPart);
        $this->filterEntity = $filterEntity;

        // dd($filterEntity);
    }

    private function initFilterProperties(): void
    {
        foreach (array_keys($this->filterEntity) as $key) {
            $prop = $key ;

            if (property_exists($this, $prop) && $this->$prop === null) {
                $this->$prop = [];
            }
        }
    }

    public function filterItems(
            string $entity,
            ?array $colorFilter       = null,
            ?array $skincolorFilter   = null,
            ?array $shapeFilter       = null,
            ?array $morphologieFilter = null,
            ?array $morphotypeFilter  = null,
            ?array $clothesFilter     = null,
            ?array $collectionFilter     = null,
        )
    {

        $arrayOfFilter = array_filter(
            [
                $colorFilter,
                $skincolorFilter,
                $shapeFilter,
                $morphologieFilter,
                $morphotypeFilter,
                $clothesFilter,
                $collectionFilter
            ],
            fn($filter)=>!is_null($filter)
        );

        // findAllByFilters est preszent dans tout les repository des enntité, tout les arguments sont initialisé a null
        $this->items = $this->entityManager->getRepository($entity)->findAllByFilters(...$arrayOfFilter);


    }

    public function fetchFilterItem(array $filterEntites)
    {
        $filterItems = [];
        foreach ($filterEntites as $key => $value) {
            // $this->entityManager->getRepository($entity)->findAll();
            $filterItems[$key] = $this->entityManager->getRepository($value)->findAll();
        }
        $this->filterItems = $filterItems;
    }

}
