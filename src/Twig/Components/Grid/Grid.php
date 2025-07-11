<?php

namespace App\Twig\Components\Grid;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent]
class Grid extends AbstractController
{
    use DefaultActionTrait;
    
    public function __construct(
        private BodyPartRegistryResolver $bodyPartRegistryResolver,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ){}

    //? ======== body part
    #[LiveProp]
    public ?string $bodypart = "" ;

    //? ======== Entity
    public ?string $itemsEntity = null;
    
    //? ======== filter Entity
    public ?array $filterEntity = null;

    //? ======== filter stock ids of filter
    
    #[LiveProp(writable: true)]
    public ?array $colorFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $shapeFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $skincolorFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $morphologieFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $morphotypeFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $clothesFilter = null;
    
    #[LiveProp(writable: true)]
    public ?array $collectionFilter = null;

    //? ===========

    //item a afficher
    public ?array $items = [];
    
    //item de filtre
    public ?array $filterItems = [];



    
    public function mount(string $bodypart){
        $route = $this->requestStack->getCurrentRequest()->headers->get('referer');
        $this->bodypart = $bodypart;
        $this->getItemEntity($this->bodypart);
        $this->getFiltersEntity($this->bodypart);
        $this->initFilterProperties();
        $this->fetchFilterItem($this->filterEntity);
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
        dump($route);
    }
    #[LiveAction]
    public function livefilterOnChange(){
        $route = $this->requestStack->getCurrentRequest()->headers->get('referer');
        // dump($route);
        $this->bodypart = explode('?type=',$route)[1];

        $this->getItemEntity($this->bodypart);
        $this->getFiltersEntity($this->bodypart);
        $this->initFilterProperties();
        $this->fetchFilterItem($this->filterEntity);
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
