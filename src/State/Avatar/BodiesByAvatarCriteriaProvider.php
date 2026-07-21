<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\BodyByAvatarCriteria;
use App\ApiResource\Avatar\BodyByAvatarCriteriaList;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Skincolor;
use App\Repository\Avatar\Body\BodyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<BodyByAvatarCriteriaList> */
final readonly class BodiesByAvatarCriteriaProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BodyRepository $bodyRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): BodyByAvatarCriteriaList
    {
        $skinColorId = $this->positiveId($uriVariables['id'] ?? null, 'Couleur de peau introuvable.');
        $morphologyId = $this->positiveId($uriVariables['morphologyId'] ?? null, 'Morphologie introuvable.');
        $morphotypeId = $this->positiveId($uriVariables['morphotypeId'] ?? null, 'Morphotype introuvable.');

        $skinColor = $this->entityManager->find(Skincolor::class, $skinColorId);
        if (!$skinColor instanceof Skincolor) {
            throw new NotFoundHttpException(sprintf('La couleur de peau %d est introuvable.', $skinColorId));
        }

        $morphology = $this->entityManager->find(Morphologie::class, $morphologyId);
        if (!$morphology instanceof Morphologie) {
            throw new NotFoundHttpException(sprintf('La morphologie %d est introuvable.', $morphologyId));
        }

        $morphotype = $this->entityManager->find(Morphotype::class, $morphotypeId);
        if (!$morphotype instanceof Morphotype) {
            throw new NotFoundHttpException(sprintf('Le morphotype %d est introuvable.', $morphotypeId));
        }

        if ($morphotype->getMorphologie()?->getId() !== $morphologyId) {
            throw new NotFoundHttpException('Le morphotype ne correspond pas à la morphologie sélectionnée.');
        }

        $clothes = $this->clothesParameter();
        $bodies = [];
        foreach ($this->bodyRepository->findForAvatarSelection($skinColor, $morphotype, $clothes) as $body) {
            $id = $body->getId();
            $image = $body->getImage();
            if ($id === null || $image === null || $image === '') {
                continue;
            }

            $bodies[] = new BodyByAvatarCriteria(
                id: $id,
                name: (string) $body->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new BodyByAvatarCriteriaList(
            skinColorId: $skinColorId,
            morphologyId: $morphologyId,
            morphotypeId: $morphotypeId,
            clothes: $clothes,
            bodies: $bodies,
        );
    }

    private function clothesParameter(): int|string|null
    {
        $value = $this->requestStack->getCurrentRequest()?->query->get('clothes');
        if ($value === null || $value === '' || strtolower((string) $value) === 'null') {
            return null;
        }

        $value = trim((string) $value);

        return ctype_digit($value) && (int) $value > 0 ? (int) $value : $value;
    }

    private function positiveId(mixed $value, string $errorMessage): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new NotFoundHttpException($errorMessage);
        }

        return $id;
    }

    private function absoluteUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request === null ? $path : $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
    }
}
