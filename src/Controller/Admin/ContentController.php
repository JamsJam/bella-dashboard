<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContentController extends AbstractController
{
    #[Route('/admin/content', name: 'app_admin_content')]
    public function index(): Response
    {
        return $this->render('admin/content/index.html.twig', [
            'controller_name' => 'ContentController',
        ]);
    }
}
