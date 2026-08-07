<?php

namespace App\Controller\UI;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FlashController extends AbstractController
{
    private const FRAME_ID = '_flash_component';

    #[Route('/ui/flashes', name: 'app_ui_flashes', methods: ['GET'])]
    public function __invoke(
        Request $request,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ): Response {
        if (self::FRAME_ID === $request->headers->get('Turbo-Frame')) {
            $response = $this->render('ui/components/_flash.html.twig');
            $response->setPrivate();
            $response->headers->set('Cache-Control', 'no-store, private');

            return $response;
        }

        if ('dev' === $environment) {
            $flashes = [];

            foreach ($request->getSession()->getFlashBag()->all() as $type => $messages) {
                foreach ($messages as $message) {
                    $flashes[] = [
                        'type' => $type,
                        'message' => $message,
                    ];
                }
            }

            return $this->json(['flashes' => $flashes]);
        }

        return new JsonResponse(
            ['error' => 'This endpoint only accepts Turbo Frame requests.'],
            Response::HTTP_FORBIDDEN,
        );
    }
}
