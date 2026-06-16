<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\PageConfigDto;
use App\Application\Config\Form\PageConfigType;
use App\Application\Config\Service\PageConfigService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageConfigController extends AbstractConfigController
{
    #[Route('/config/pages', name: 'app_config_pages', methods: ['GET'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        PageConfigService $configService,
    ): Response {
        return $this->render('config/pages/index.html.twig', [
            'pages' => $configService->all(),
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
        ]);
    }

    #[Route('/config/pages/create', name: 'app_config_pages_create', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $slug = strtolower(trim((string) $request->request->get('slug', 'home')));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?: 'home';
        $slug = trim($slug, '-_') ?: 'home';

        return $this->redirectToRoute('app_config_page', ['slug' => $slug]);
    }

    #[Route('/config/page/{slug}', name: 'app_config_page', methods: ['GET', 'POST'], defaults: ['slug' => 'home'])]
    public function __invoke(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        PageConfigService $configService,
        FlashService $flashService,
        ?string $slug = 'home',
    ): Response {
        $config = $configService->get($slug);
        $form = $this->createForm(PageConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PageConfigDto $config */
            $config = $form->getData();
            $configService->save($config, $this->extractSectionImageFiles($form));
            $flashService->success('Configuration de page mise à jour.');

            return $this->redirectToRoute('app_config_page', ['slug' => $config->normalizedSlug()]);
        }

        return $this->renderFormPage($request, $breadscrumbs, 'Configuration shop', $form->createView(), [
            'subtitle' => sprintf('Page front : %s', $config->normalizedSlug()),
        ]);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function extractSectionImageFiles(FormInterface $form): array
    {
        $files = [];

        if (!$form->has('sections')) {
            return $files;
        }

        foreach ($form->get('sections') as $index => $sectionForm) {
            if (!$sectionForm->has('imageFile')) {
                continue;
            }

            $file = $sectionForm->get('imageFile')->getData();
            if ($file instanceof UploadedFile) {
                $files[(int) $index] = $file;
            }
        }

        return $files;
    }
}
