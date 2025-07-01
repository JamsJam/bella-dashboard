<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'app_front_project')]
    public function index(): Response
    {
        return $this->render('front/project/index.html.twig', [
            'controller_name' => 'ProjectController',
        ]);
    }

    #[Route('/project', name: 'app_front_project')]
    public function show(): Response
    {
        return $this->render('front/project/index.html.twig', [
            'controller_name' => 'ProjectController',
        ]);
    }
}
