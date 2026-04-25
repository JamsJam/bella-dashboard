<?php

namespace App\Controller\Setting;

use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    #[Route('/config/change-theme', name: 'app_admin_config_theme', methods: ['POST'])]
    public function changeTheme(
        ThemeService $theme_service,
        Request $request,
    ): Response {
        $theme = $request->getPayload()->get('theme');

        try {
            $theme_service->setTheme($theme);

            return $this->json('');
        } catch (\Throwable $th) {
            // throw $th;
            return $this->json('', 500);
        }
    }
}
