<?php

namespace App\Controller\Config;

use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractConfigController extends AbstractController
{
    /**
     * @param array<string, mixed> $context
     */
    protected function renderFormPage(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        string $title,
        FormView $form,
        array $context = [],
    ): Response {
        return $this->render('config/form.html.twig', $context + [
            'title' => $title,
            'subtitle' => null,
            'form' => $form,
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
        ]);
    }
}
