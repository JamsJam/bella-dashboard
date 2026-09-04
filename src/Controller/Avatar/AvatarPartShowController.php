<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Exception\AvatarPartNotFoundException;
use App\Application\Avatar\Services\AvatarPartDetailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AvatarPartShowController extends AbstractController
{
    #[Route('/avatar/{part}/{id}', name: 'app_avatar_part_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(string $part, int $id, AvatarPartDetailService $detailService): Response
    {
        try {
            $detail = $detailService->getDetail($part, $id);
        } catch (AvatarPartNotFoundException) {
            throw $this->createNotFoundException('Avatar part not found.');
        }

        return $this->render('avatar/show.html.twig', [
            'breadscrumbs' => [
                ['label' => 'Avatar', 'route' => 'app_avatar'],
                ['label' => $detail->avatar->name],
            ],
            'part' => $detail->part,
            'avatar' => $detail->avatar,
            'similarAvatars' => $detail->similarAvatars,
            'accessoryFaces' => $detail->accessoryFaces,
            'showAccessoryFacesSection' => $detail->showAccessoryFacesSection,
        ]);
    }
}
