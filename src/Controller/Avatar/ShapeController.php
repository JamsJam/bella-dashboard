<?php

namespace App\Controller\Avatar;

use App\Enum\Avatar\BodyPartEnum;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShapeController extends AbstractController
{
    #[Route('/avatar/shape', name: 'app_avatar_shape_index')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        ?BodyPartEnum $type,
        EntityManagerInterface $entityManagerInterface,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        ): Response
    {
        // ? ==== define type
        $type =  BodyPartEnum::tryFrom($request->query->get('type'));
        $parts = null;
        if (BodyPartEnum::BODY === $type || BodyPartEnum::SKIN === $type) {
            $type = null;
        }

        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        if (null !== $type) {
            $metaData->addBreadcrumb((new BreadcrumbItemDTO())
            ->setTitle($type->value)
            ->setRoute('app_avatar_index')
            );

            $entity = $bodyPartRegistryResolver->getEntity('shape', $type->value);
            $filterEntities = $bodyPartRegistryResolver->getFilters($type->value);

            $filterItems = [];
            $usedFilters = [];
            // dd($request->query);
            foreach ($filterEntities as $key => $value) {
                $filterItems[$key] = $entityManagerInterface->getRepository($value)->findAll();
                if ($request->query->all($key)) {
                    $usedFilters[$key] = $request->query->all($key);
                }
            }
            // dd($usedFilters);

            $allPart = [];
            if ([] === $usedFilters) {
                $allPart = $entityManagerInterface->getRepository($entity)->findAll();
            } else {
                $normalizedFilters = [];
                $filterMap = [
                    'colorFilter' => 'color',
                    'shapeFilter' => 'shape',
                    'skincolorFilter' => 'skincolor',
                    'morphologieFilter' => 'morphologie',
                    'morphotypeFilter' => 'morphotype',
                    'clothesFilter' => 'clothes',
                    'collectionsFilter' => 'collections',
                ];
                foreach ($usedFilters as $key => $values) {
                    if (isset($filterMap[$key])) {
                        $normalizedFilters[$filterMap[$key]] = $values;
                    }
                }
                // dd(...$normalizedFilters);
                $allPart = $entityManagerInterface->getRepository($entity)->findAllByFilters(...$normalizedFilters);
            }
            // dd($filterItems);

            // type!=null (Avatar bodypart index)
            return $this->render('avatar/index.html.twig', [
                'metaData' => $metaData,
                'type' => $type->value,
                'parts' => $parts,
                'bodyPartItems' => $allPart,
                'filters' => $filterItems,
            ]);
        }

        $typeChoice = [
            'hair' => 'formes de cheveux',
            'mouth' => 'formes de bouche',
            'eye' => 'formes des yeux',
            'eyebrows' => 'formes des sourcils',
            'nose' => 'formes de nez',
            'face' => 'formes de visage',
        ];

        // type=null (Avatar Hub)
        return $this->render('avatar/shape/index.html.twig', [
            'metaData' => $metaData,
            'type' => $type,
            'shapeChoice' => $typeChoice,
            'parts' => $parts,
        ]);
    }
}
