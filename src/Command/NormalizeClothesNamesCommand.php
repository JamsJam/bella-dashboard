<?php

namespace App\Command;

use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Entity\Clothes\Clothes;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:clothes:normalize-names',
    description: 'Réduit les noms des vêtements au premier mot en minuscules et recalcule les slugs existants.',
)]
final class NormalizeClothesNamesCommand extends Command
{
    public function __construct(
        private readonly ClothesRepository $clothesRepository,
        private readonly ClotheNameGuard $clotheNameGuard,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clothes = $this->clothesRepository->findAll();
        $updatedClothes = 0;
        $updatedSlugs = 0;
        $mergedClothes = 0;
        $now = new \DateTimeImmutable();

        $groups = [];
        foreach ($clothes as $clothe) {
            if ($clothe instanceof Clothes) {
                $groups[$this->clotheNameGuard->normalizeName((string) $clothe->getName())][] = $clothe;
            }
        }

        foreach ($groups as $name => $group) {
            $canonical = null;
            foreach ($group as $candidate) {
                if ($candidate->getName() === $name) {
                    $canonical = $candidate;
                    break;
                }
            }
            $canonical ??= $group[0];

            foreach ($group as $duplicate) {
                if ($duplicate === $canonical) {
                    continue;
                }

                foreach ($duplicate->getVariants()->toArray() as $variant) {
                    $canonical->addVariant($variant);
                }

                $this->entityManager->remove($duplicate);
                ++$mergedClothes;
            }

            if ($canonical->getName() !== $name) {
                $canonical->setName($name);
                ++$updatedClothes;
            }

            foreach ($canonical->getVariants() as $variant) {
                $colorName = trim((string) $variant->getColor()?->getName());
                if ($colorName === '') {
                    continue;
                }

                $slug = strtolower((string) (new AsciiSlugger())->slug($name.' '.$colorName));
                if ($variant->getSlug() !== $slug) {
                    $variant->setSlug($slug)->setEditedAt($now);
                    ++$updatedSlugs;
                }
            }

            $canonical->setEditedAt($now);
        }

        $this->entityManager->flush();
        $io->success(sprintf(
            '%d nom(s), %d slug(s) et %d parent(s) en doublon ont été normalisés. Aucun variant n’a été créé.',
            $updatedClothes,
            $updatedSlugs,
            $mergedClothes,
        ));

        return Command::SUCCESS;
    }
}
