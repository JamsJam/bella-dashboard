<?php

namespace App\Controller\Clothes\Collection;

use App\Application\Clothes\Mapper\CollectionClothesMapper;
use App\Entity\Collections\Collections;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListCollectionClothesController extends AbstractController
{
    #[Route('/collections/{id}/clothes', name: 'app_clothes_collection_clothes', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function list(Collections $collection, CollectionClothesMapper $mapper): Response
    {
        return $this->render('clothes/collections/_clothes_list.html.twig', [
            'clothes' => $mapper->map($collection),
        ]);
    }
}
