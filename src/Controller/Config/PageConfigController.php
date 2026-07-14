<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\PageConfigDto;
use App\Application\Config\Dto\Page\Categories\CategoriesConfigDto;
use App\Application\Config\Dto\Page\Homepage\HomepageConfigDto;
use App\Application\Config\Dto\Page\Homepage\Item\ManualItemDto;
use App\Application\Config\Dto\Page\Homepage\Item\ReturnStepDto;
use App\Application\Config\Form\PageConfigType;
use App\Application\Config\Form\Page\Categories\CategoriesConfigType;
use App\Application\Config\Form\Page\Homepage\HomepageConfigType;
use App\Application\Config\Provider\HomepageConfigProvider;
use App\Application\Config\Provider\CategoriesConfigProvider;
use App\Application\Config\Service\HomepageImageUploader;
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
        HomepageConfigProvider $homepageConfigProvider,
        CategoriesConfigProvider $categoriesConfigProvider,
        HomepageImageUploader $homepageImageUploader,
        FlashService $flashService,
        ?string $slug = 'home',
    ): Response {
        if ($slug === 'homepage') {
            return $this->homepage(
                $request,
                $breadscrumbs,
                $homepageConfigProvider,
                $homepageImageUploader,
                $flashService,
            );
        }

        if ($slug === 'categories') {
            return $this->categories(
                $request,
                $breadscrumbs,
                $categoriesConfigProvider,
                $homepageImageUploader,
                $flashService,
            );
        }

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
            'back_url' => $this->generateUrl('app_config_pages'),
            'back_label' => 'Retour aux pages',
        ]);
    }

    private function homepage(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        HomepageConfigProvider $provider,
        HomepageImageUploader $imageUploader,
        FlashService $flashService,
    ): Response {
        $config = $provider->get();
        $previousImagePath = $config->landing->image;
        $previousOpenGraphImagePath = $config->seo->ogImage;
        $previousManualImagePaths = array_map(
            static fn (ManualItemDto $item): string => $item->image,
            $config->manual->list,
        );
        $previousReturnIconPaths = array_map(
            static fn (ReturnStepDto $step): string => $step->icon,
            $config->return->steps,
        );
        $form = $this->createForm(HomepageConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var HomepageConfigDto $config */
            $config = $form->getData();
            $imageFile = $form->get('landing')->get('imageFile')->getData();
            if ($imageFile instanceof UploadedFile) {
                $config->landing->image = $imageUploader->uploadLandingImage($imageFile);
            }

            $openGraphImageFile = $form->get('seo')->get('ogImageFile')->getData();
            if ($openGraphImageFile instanceof UploadedFile) {
                $config->seo->ogImage = $imageUploader->uploadOpenGraphImage($openGraphImageFile);
            }

            $replacedManualImages = [];
            foreach ($form->get('manual')->get('list') as $index => $itemForm) {
                $manualImageFile = $itemForm->get('imageFile')->getData();
                $manualItem = $config->manual->list[(int) $index] ?? null;
                if (!$manualImageFile instanceof UploadedFile || !$manualItem instanceof ManualItemDto) {
                    continue;
                }

                $manualItem->image = $imageUploader->uploadManualImage($manualImageFile, (int) $index + 1);
                $replacedManualImages[] = $previousManualImagePaths[(int) $index] ?? null;
            }

            $replacedReturnIcons = [];
            foreach ($form->get('return')->get('steps') as $index => $stepForm) {
                $returnIconFile = $stepForm->get('iconFile')->getData();
                $returnStep = $config->return->steps[(int) $index] ?? null;
                if (!$returnIconFile instanceof UploadedFile || !$returnStep instanceof ReturnStepDto) {
                    continue;
                }

                $returnStep->icon = $imageUploader->uploadReturnIcon($returnIconFile, (int) $index + 1);
                $replacedReturnIcons[] = $previousReturnIconPaths[(int) $index] ?? null;
            }

            $provider->save($config);
            if ($imageFile instanceof UploadedFile && $previousImagePath !== $config->landing->image) {
                $imageUploader->removePreviousImage($previousImagePath);
            }
            if ($openGraphImageFile instanceof UploadedFile && $previousOpenGraphImagePath !== $config->seo->ogImage) {
                $imageUploader->removePreviousImage($previousOpenGraphImagePath);
            }
            foreach ($replacedManualImages as $previousManualImagePath) {
                $imageUploader->removePreviousImage($previousManualImagePath);
            }
            foreach ($replacedReturnIcons as $previousReturnIconPath) {
                $imageUploader->removePreviousImage($previousReturnIconPath);
            }
            $flashService->success('Configuration de la page d’accueil mise à jour.');

            return $this->redirectToRoute('app_config_page', ['slug' => 'homepage']);
        }

        return $this->renderFormPage($request, $breadscrumbs, 'Configuration de la page d’accueil', $form->createView(), [
            'back_url' => $this->generateUrl('app_config_pages'),
            'back_label' => 'Retour aux pages',
            'homepage_form' => true,
        ]);
    }

    private function categories(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        CategoriesConfigProvider $provider,
        HomepageImageUploader $imageUploader,
        FlashService $flashService,
    ): Response {
        $config = $provider->get();
        $previousBannerPath = $config->bandeau->background;
        $previousOpenGraphPath = $config->seo->ogImage;
        $form = $this->createForm(CategoriesConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CategoriesConfigDto $config */
            $config = $form->getData();
            $bannerFile = $form->get('bandeau')->get('backgroundFile')->getData();
            $openGraphFile = $form->get('seo')->get('ogImageFile')->getData();

            if ($bannerFile instanceof UploadedFile) {
                $config->bandeau->background = $imageUploader->uploadCategoriesBanner($bannerFile);
            }
            if ($openGraphFile instanceof UploadedFile) {
                $config->seo->ogImage = $imageUploader->uploadCategoriesOpenGraphImage($openGraphFile);
            }

            $provider->save($config);

            if ($bannerFile instanceof UploadedFile && $previousBannerPath !== $config->bandeau->background) {
                $imageUploader->removePreviousImage($previousBannerPath);
            }
            if ($openGraphFile instanceof UploadedFile && $previousOpenGraphPath !== $config->seo->ogImage) {
                $imageUploader->removePreviousImage($previousOpenGraphPath);
            }

            $flashService->success('Configuration de la page des catégories mise à jour.');

            return $this->redirectToRoute('app_config_page', ['slug' => 'categories']);
        }

        return $this->renderFormPage($request, $breadscrumbs, 'Configuration des catégories', $form->createView(), [
            'back_url' => $this->generateUrl('app_config_pages'),
            'back_label' => 'Retour aux pages',
            'categories_form' => true,
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
