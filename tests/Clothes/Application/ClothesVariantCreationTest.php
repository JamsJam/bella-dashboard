<?php

namespace App\Tests\Clothes\Application;

use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Entity\Users\Admin;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/** Vérifie le parcours HTTP complet du formulaire d’ajout de variantes. */
#[Group('clothes')]
#[Group('application')]
final class ClothesVariantCreationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;
    private Clothes $clothe;
    private Clothescolor $color;
    private Clothessize $small;
    private Clothessize $medium;
    private string $token;
    private string $uploadDirectory;

    protected function setUp(): void
    {
        $this->token = bin2hex(random_bytes(2));
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertTestDatabase();

        $now = new \DateTimeImmutable();
        $admin = (new Admin())
            ->setEmail($this->token . '@example.test')
            ->setPassword('mot-de-passe-de-test')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $category = (new Category())
            ->setName('Catégorie ' . $this->token)
            ->setSlug('categorie-' . $this->token)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $collection = (new Collections())
            ->setName('Collection ' . $this->token)
            ->setCategory($category)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $clothe = (new Clothes())
            ->setName('Vêtement ' . $this->token)
            ->setPrice(5900)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $color = (new Clothescolor())
            ->setName('Bleu ' . $this->token)
            ->setHexa('112233')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $small = (new Clothessize())->setName('s' . $this->token)->setCreatedAt($now)->setEditedAt($now);
        $medium = (new Clothessize())->setName('m' . $this->token)->setCreatedAt($now)->setEditedAt($now);

        foreach ([$admin, $category, $collection, $clothe, $color, $small, $medium] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');
        $this->clothe = $clothe;
        $this->color = $color;
        $this->small = $small;
        $this->medium = $medium;
    }

    public function testAdministratorCanCreateOnePublishableVariantPerSelectedSize(): void
    {
        // Le GET vérifie la route, la sécurité, Twig et la structure accessible du formulaire.
        $crawler = $this->client->request('GET', '/clothes/variants/add');
        self::assertResponseIsSuccessful('Blocage : la page d’ajout de variantes ne répond pas correctement.');
        self::assertSelectorTextContains('h1', 'Ajouter des variantes', 'Blocage : le titre du formulaire est absent.');
        self::assertSelectorExists('form[name="variant"]', 'Blocage : le formulaire Symfony des variantes est absent du DOM.');
        self::assertSelectorExists('.clothe-create__size-input', 'Blocage : les tailles ne sont pas rendues sous forme de choix.');
        self::assertSelectorExists('[data-clothe-color-select]', 'Blocage : le choix entre couleur existante et nouvelle couleur est absent.');

        $csrfToken = (string) $crawler->filter('input[name="variant[_token]"]')->attr('value');
        self::assertNotSame('', $csrfToken, 'Blocage : le formulaire ne contient aucun jeton CSRF.');

        $sourceImage = tempnam(sys_get_temp_dir(), 'bellagp-variant-');
        if (false === $sourceImage) {
            self::fail('Blocage de préparation : impossible de créer l’image temporaire du test.');
        }
        file_put_contents(
            $sourceImage,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );

        $expectedSlug = 'vetement-' . $this->token . '-bleu-' . $this->token;
        $this->uploadDirectory = dirname(__DIR__, 3) . '/public/images/upload/clothes/' . $expectedSlug;

        // Le POST traverse le contrôleur, la validation, la factory, Doctrine et le workflow.
        $this->client->request(
            'POST',
            '/clothes/variants/add',
            [
                'variant' => [
                    'clothe' => (string) $this->clothe->getId(),
                    'color' => (string) $this->color->getId(),
                    'newColorName' => '',
                    'newColorHex' => '#000000',
                    'sizes' => [(string) $this->small->getId(), (string) $this->medium->getId()],
                    'description' => 'Description applicative',
                    'metaDescription' => 'Méta-description applicative',
                    '_token' => $csrfToken,
                ],
            ],
            [
                'variant' => [
                    'images' => [new UploadedFile($sourceImage, 'variant.png', 'image/png', null, true)],
                ],
            ],
        );

        self::assertResponseRedirects(
            '/clothes/' . $expectedSlug,
            message: 'Blocage : après création, le formulaire ne redirige pas vers le vêtement attendu.',
        );

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->clear();
        $createdVariants = $this->entityManager->getRepository(ClothesVariant::class)->findBy(
            ['clothes' => $this->clothe->getId()],
            ['id' => 'ASC'],
        );

        self::assertCount(2, $createdVariants, 'Blocage : le formulaire n’a pas créé une variante par taille.');
        foreach ($createdVariants as $variant) {
            self::assertSame($expectedSlug, $variant->getSlug(), 'Blocage : les variantes de même couleur ne partagent pas leur slug.');
            self::assertSame(ClotheStatus::Publishable, $variant->getPublicationStatus(), 'Blocage : une variante complète ne devient pas publiable.');
            self::assertNotEmpty($variant->getImages(), 'Blocage : les images envoyées ne sont pas enregistrées sur la variante.');
        }
    }

    private function assertTestDatabase(): void
    {
        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        self::assertStringEndsWith(
            '_test',
            $databaseName,
            sprintf('Sécurité : refus d’exécuter le test applicatif sur la base « %s ».', $databaseName),
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager, $this->token)) {
            $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $this->entityManager->clear();
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement('DELETE FROM clothes_variant WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM clothes WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM clothescolor WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM clothessize WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM collections WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM category WHERE name LIKE :token', ['token' => '%' . $this->token . '%']);
            $connection->executeStatement('DELETE FROM admin WHERE email = :email', ['email' => $this->token . '@example.test']);
        }

        if (isset($this->uploadDirectory) && is_dir($this->uploadDirectory)) {
            foreach (glob($this->uploadDirectory . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->uploadDirectory);
        }

        parent::tearDown();
    }
}
