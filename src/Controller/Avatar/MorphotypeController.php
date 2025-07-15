<?php

namespace App\Controller\Avatar;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MorphotypeController extends AbstractController
{
    #[Route('/avatar/morphotype', name: 'app_avatar_morphotype')]
    public function index(): Response
    {
        return $this->render('avatar/morphotype/index.html.twig', [
            'controller_name' => 'Avatar/MorphotypeController',
        ]);
    }
}
