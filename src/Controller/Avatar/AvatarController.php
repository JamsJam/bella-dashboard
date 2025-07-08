<?php

namespace App\Controller\Avatar;

use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use App\Enum\Avatar\BodyPartEnum;
use App\Provider\PageMetadata\PageMetadataProvider;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AvatarController extends AbstractController
{
    #[Route('/avatar', name: 'app_avatar_index')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        ?BodyPartEnum $type,
    ): Response {

        // ? ==== define type
        $type = BodyPartEnum::tryFrom($request->query->get('type'));
        $parts = null;
        if (BodyPartEnum::SKIN === $type) {
            $type = null;
        }

        $typeChoice = null !== $type ? null : [
            'body' => 'corps',
            'face' => 'visage',
            'nose' => 'nez',
            'hair' => 'cheveux',
            'mouth' => 'bouche',
            'eye' => 'yeux',
            'eyebrows' => 'sourcils',
        ];

        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        if (null !== $type) {
            $metaData->addBreadcrumb((new BreadcrumbItemDTO())
            ->setTitle($type->value)
            ->setRoute('app_avatar_index')
            );
        }

        return $this->render('avatar/index.html.twig', [
            'metaData' => $metaData,
            'type' => $type->value ?? $type,
            'typeChoice' => $typeChoice,
            'parts' => $parts,
        ]);
    }
}
