<?php

namespace App\Controller\Avatar;

use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Entity\Avatar\Skincolor;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AvatarColorController extends AbstractController
{
    private const TYPES = [
        'skin' => [
            'label' => 'Peau',
            'class' => Skincolor::class,
            'associations' => ['getNoses', 'getBodies', 'getFaces'],
        ],
        'hair' => [
            'label' => 'Cheveux',
            'class' => Hairscolor::class,
            'associations' => ['getHairs'],
        ],
        'eyes' => [
            'label' => 'Yeux',
            'class' => Eyecolor::class,
            'associations' => ['getEyes'],
        ],
        'eyebrows' => [
            'label' => 'Sourcils',
            'class' => Eyebrowscolor::class,
            'associations' => ['getEyebrows'],
        ],
        'mouth' => [
            'label' => 'Bouche',
            'class' => Mouthscolor::class,
            'associations' => ['getMouths'],
        ],
    ];

    #[Route(
        '/avatar/colors/modal/{type}',
        name: 'app_avatar_colors_modal',
        defaults: ['type' => 'skin'],
        requirements: ['type' => 'skin|hair|eyes|eyebrows|mouth'],
        methods: ['GET'],
    )]
    public function modal(
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $type = 'skin',
    ): Response {
        return $this->renderModal($type, $entityManager, $csrfTokenManager);
    }

    #[Route(
        '/avatar/colors/{type}/{id}/delete',
        name: 'app_avatar_color_delete',
        requirements: ['type' => 'skin|hair|eyes|eyebrows|mouth', 'id' => '\\d+'],
        methods: ['POST'],
    )]
    public function delete(
        string $type,
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerService $logger,
    ): Response {
        $definition = self::TYPES[$type];
        $color = $entityManager->find($definition['class'], $id);

        if (!is_object($color)) {
            throw $this->createNotFoundException('Couleur d’avatar introuvable.');
        }

        $token = new CsrfToken(
            $this->csrfTokenId($type, $id),
            (string) $request->request->get('_csrf_token', ''),
        );

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for avatar color deletion.', [
                'color_type' => $type,
                'color_id' => $id,
            ]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $associatedElements = $this->associatedElements($color, $definition['associations']);

        foreach ($associatedElements as $element) {
            $entityManager->remove($element);
        }

        $entityManager->remove($color);
        $entityManager->flush();

        $logger->info('Avatar color deleted.', [
            'color_type' => $type,
            'color_id' => $id,
            'associated_elements_deleted' => count($associatedElements),
        ]);

        return $this->renderModal($type, $entityManager, $csrfTokenManager);
    }

    private function renderModal(
        string $activeType,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $definition = self::TYPES[$activeType];
        $colors = [];

        foreach ($entityManager->getRepository($definition['class'])->findBy([], ['name' => 'ASC']) as $color) {
            if (!method_exists($color, 'getId') || !method_exists($color, 'getName') || !method_exists($color, 'getHexa')) {
                continue;
            }

            $id = $color->getId();
            if (!is_int($id)) {
                continue;
            }

            $colors[] = [
                'id' => $id,
                'name' => (string) $color->getName(),
                'hexa' => $color->getHexa(),
                'associatedCount' => count($this->associatedElements($color, $definition['associations'])),
                'deleteUrl' => $this->generateUrl('app_avatar_color_delete', [
                    'type' => $activeType,
                    'id' => $id,
                ]),
                'csrfToken' => $csrfTokenManager->getToken($this->csrfTokenId($activeType, $id))->getValue(),
            ];
        }

        $tabs = [];
        foreach (self::TYPES as $type => $typeDefinition) {
            $tabs[] = [
                'type' => $type,
                'label' => $typeDefinition['label'],
                'href' => $this->generateUrl('app_avatar_colors_modal', ['type' => $type]),
                'active' => $type === $activeType,
            ];
        }

        $html = $this->renderView('avatar/_colors_modal.html.twig', [
            'activeLabel' => $definition['label'],
            'colors' => $colors,
            'tabs' => $tabs,
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    /**
     * @param list<string> $associationMethods
     *
     * @return list<object>
     */
    private function associatedElements(object $color, array $associationMethods): array
    {
        $elements = [];

        foreach ($associationMethods as $method) {
            if (!method_exists($color, $method)) {
                continue;
            }

            foreach ($color->{$method}() as $element) {
                if (is_object($element)) {
                    $elements[spl_object_id($element)] = $element;
                }
            }
        }

        return array_values($elements);
    }

    private function csrfTokenId(string $type, int $id): string
    {
        return sprintf('avatar_color_delete_%s_%d', $type, $id);
    }
}
