<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\DTO\VariantGroupInput;
use App\Application\Clothes\Services\ClotheVariantFactory;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/** Vérifie la construction des variantes sans conteneur Symfony ni base de données. */
#[Group('clothes')]
#[Group('unit')]
final class ClotheVariantFactoryTest extends TestCase
{
    private string $temporaryProjectDir;

    protected function setUp(): void
    {
        $this->temporaryProjectDir = sys_get_temp_dir() . '/bellagp-clothes-unit-' . bin2hex(random_bytes(5));
        mkdir($this->temporaryProjectDir, 0775, true);
    }

    public function testOneVariantIsCreatedPerSizeWithSharedColorPropertiesAndSlug(): void
    {
        $imagePath = $this->temporaryProjectDir . '/source.png';
        file_put_contents($imagePath, 'image de test');

        $clothe = (new Clothes())->setName('Robe Éclipse')->setPrice(5900);
        $color = (new Clothescolor())->setName('Bleu Nuit')->setHexa('112233');
        $small = (new Clothessize())->setName('S');
        $medium = (new Clothessize())->setName('M');
        $input = new VariantGroupInput();
        $input->color = $color;
        $input->sizes = [$small, $medium];
        $input->description = 'Description commune';
        $input->metaDescription = 'Méta-description commune';
        $input->images = [new UploadedFile($imagePath, 'source.png', 'image/png', null, true)];

        $factory = new ClotheVariantFactory(
            $this->createStub(EntityManagerInterface::class),
            $this->temporaryProjectDir,
        );
        $variants = $factory->createGroup($clothe, $input);

        self::assertCount(2, $variants, 'Blocage : une variante doit être créée pour chaque taille sélectionnée.');
        self::assertSame('robe-eclipse-bleu-nuit', $variants[0]->getSlug(), 'Blocage : le slug commun couleur est incorrect.');
        self::assertSame($variants[0]->getSlug(), $variants[1]->getSlug(), 'Blocage : les tailles d’une couleur ne partagent pas le même slug.');
        self::assertSame('Robe Éclipse Bleu Nuit S', $variants[0]->getName(), 'Blocage : le nom de la variante ne contient pas vêtement, couleur et taille.');
        self::assertSame('Robe Éclipse Bleu Nuit M', $variants[1]->getName(), 'Blocage : le nom de la deuxième taille est incorrect.');
        self::assertSame(ClotheStatus::Draft, $variants[0]->getPublicationStatus(), 'Blocage : une nouvelle variante doit commencer en brouillon.');
        self::assertSame('Description commune', $variants[1]->getDescription(), 'Blocage : la description du groupe n’est pas partagée.');
        self::assertSame('Méta-description commune', $variants[1]->getMetadescription(), 'Blocage : la métadescription du groupe n’est pas partagée.');
        self::assertSame(0, $variants[0]->getStock(), 'Blocage : le stock initial d’une variante doit être égal à zéro.');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->temporaryProjectDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->temporaryProjectDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->temporaryProjectDir);
        }

        parent::tearDown();
    }
}
