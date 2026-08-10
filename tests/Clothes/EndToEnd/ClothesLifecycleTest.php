<?php

namespace App\Tests\Clothes\EndToEnd;

use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Entity\Users\Admin;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/** Vérifie le cycle administrateur de création, modification et suppression des variantes. */
#[Group('clothes')]
#[Group('end-to-end')]
final class ClothesLifecycleTest extends PantherTestCase
{
    private Client $client;
    private EntityManagerInterface $entityManager;
    private string $token;
    private string $email;
    private Category $category;
    private Collections $collection;
    private Clothescolor $color;
    private Clothessize $small;
    private Clothessize $medium;
    private string $sourceImage;
    private ?string $localUploadDirectory = null;

    protected function setUp(): void
    {
        $this->token = bin2hex(random_bytes(4));
        $this->email = 'clothes-e2e-' . $this->token . '@example.test';
        $this->client = self::createPantherClient(...$this->pantherOptions());
        $this->client->getWebDriver()->manage()->deleteAllCookies();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertTestDatabase();
        $this->createFixtures();
        $this->sourceImage = $this->createTemporaryPng();
        $this->authenticate();
    }

    public function testAdministratorCreatesACompleteClotheWithTwoVariants(): void
    {
        $name = 'Robe E2E ' . $this->token;
        $crawler = $this->client->request('GET', '/clothes/add');
        self::assertSelectorTextContains(
            '#clothe-add-title',
            'Ajouter un vêtement',
            'Blocage : le formulaire de création d’un vêtement n’est pas accessible.',
        );
        self::assertSelectorExists(
            '[data-controller~="clothe-form"]',
            'Blocage : le formulaire n’est pas connecté à son contrôleur Stimulus.',
        );

        $crawler->filter('input[name="clothe[name]"]')->getElement(0)->sendKeys($name);
        $crawler->filter('input[name="clothe[price]"]')->getElement(0)->sendKeys('7900');
        (new WebDriverSelect($this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('select[name="clothe[collection]"]'),
        )))->selectByValue((string) $this->collection->getId());
        (new WebDriverSelect($this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('select[name="clothe[variants][0][color]"]'),
        )))->selectByValue((string) $this->color->getId());

        foreach ([$this->small, $this->medium] as $size) {
            $sizeInput = $this->client->getWebDriver()->findElement(WebDriverBy::cssSelector(sprintf(
                'input[name="clothe[variants][0][sizes][]"][value="%d"]',
                $size->getId(),
            )));
            $this->client->getWebDriver()->findElement(
                WebDriverBy::cssSelector(sprintf('label[for="%s"]', $sizeInput->getAttribute('id'))),
            )->click();
        }

        $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('textarea[name="clothe[variants][0][description]"]'),
        )->sendKeys('Description créée dans un navigateur réel.');
        $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('textarea[name="clothe[variants][0][metaDescription]"]'),
        )->sendKeys('Méta-description E2E.');
        $imageInput = $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('input[name="clothe[variants][0][images][]"]'),
        );
        $imageInput->setFileDetector(new LocalFileDetector());
        $imageInput->sendKeys($this->sourceImage);

        $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('button.clothe-create__submit'),
        )->click();
        $this->client->waitForStaleness('form[name="clothe"]');

        $this->entityManager->clear();
        $clothe = $this->entityManager->getRepository(Clothes::class)->findOneBy(['name' => $name]);
        $feedback = trim($this->client->getCrawler()->filter('body')->text('', true));
        self::assertInstanceOf(
            Clothes::class,
            $clothe,
            sprintf(
                "Blocage : le vêtement soumis n’a pas été persisté.\nPage : %s\nRetour : %s",
                $this->client->getCurrentURL(),
                mb_substr($feedback, 0, 800),
            ),
        );
        self::assertCount(2, $clothe->getVariants(), 'Blocage : une variante par taille n’a pas été créée.');

        $expectedSlug = (new AsciiSlugger())->slug($name . ' ' . $this->color->getName())->lower()->toString();
        $this->localUploadDirectory = dirname(__DIR__, 3) . '/public/images/upload/clothes/' . $expectedSlug;
        foreach ($clothe->getVariants() as $variant) {
            self::assertSame(
                $expectedSlug,
                $variant->getSlug(),
                'Blocage : les variantes ne partagent pas leur slug couleur.',
            );
            self::assertSame(
                ClotheStatus::Publishable,
                $variant->getPublicationStatus(),
                'Blocage : une variante complète créée par le navigateur n’est pas publiable.',
            );
            self::assertNotEmpty(
                $variant->getImages(),
                'Blocage : l’image envoyée par le navigateur n’est pas conservée.',
            );
        }
    }

    public function testAdministratorAddsThenUpdatesAVariantFromTheSizesModal(): void
    {
        $clothe = $this->createClotheWithSizes(['S' => 2]);
        $this->openSizesModal($clothe);

        $mediumCheckbox = $this->sizeCheckbox('M');
        $mediumCheckbox->click();
        $this->client->waitFor('input[aria-label="Stock de la taille M"]');
        $mediumStock = $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('input[aria-label="Stock de la taille M"]'),
        );
        $mediumStock->clear();
        $mediumStock->sendKeys('7');
        $this->submitSizesModal();

        $created = $this->findVariant($clothe, 'M');
        self::assertInstanceOf(ClothesVariant::class, $created, 'Blocage : la taille M n’a créé aucune variante.');
        self::assertSame(7, $created->getStock(), 'Blocage : le stock initial de la nouvelle variante est incorrect.');
        self::assertSame(
            ClotheStatus::Draft,
            $created->getPublicationStatus(),
            'Blocage : une nouvelle taille doit démarrer en brouillon.',
        );

        $this->openSizesModal($clothe);
        $mediumStock = $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('input[aria-label="Stock de la taille M"]'),
        );
        $mediumStock->clear();
        $mediumStock->sendKeys('11');
        $this->submitSizesModal();

        self::assertSame(
            11,
            $this->findVariant($clothe, 'M')?->getStock(),
            'Blocage : la modification du stock de la variante M n’est pas persistée.',
        );
        self::assertSame(
            ClotheStatus::Publishable,
            $this->findVariant($clothe, 'M')?->getPublicationStatus(),
            'Blocage : le variant complet modifié ne passe pas automatiquement de brouillon à publiable.',
        );
    }

    public function testVariantDeletionRequiresConfirmationAndKeepsAnotherVariant(): void
    {
        $clothe = $this->createClotheWithSizes(['S' => 3, 'M' => 4]);
        $this->openSizesModal($clothe);
        $this->sizeCheckbox('M')->click();

        $this->modalSubmitButton()->click();
        $alert = $this->client->getWebDriver()->switchTo()->alert();
        self::assertStringContainsString(
            'M',
            $alert->getText(),
            'Blocage : la confirmation ne précise pas la taille qui sera supprimée.',
        );
        $alert->dismiss();

        self::assertInstanceOf(
            ClothesVariant::class,
            $this->findVariant($clothe, 'M'),
            'Blocage : annuler la confirmation a tout de même supprimé la variante.',
        );

        $modalForm = $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('#modal-root form'),
        );
        $this->modalSubmitButton()->click();
        $this->client->getWebDriver()->switchTo()->alert()->accept();
        $this->client->getWebDriver()->wait()->until(
            WebDriverExpectedCondition::stalenessOf($modalForm),
        );

        self::assertNull($this->findVariant($clothe, 'M'), 'Blocage : la variante confirmée n’a pas été supprimée.');
        self::assertInstanceOf(
            ClothesVariant::class,
            $this->findVariant($clothe, 'S'),
            'Blocage : supprimer M a également supprimé la dernière variante S.',
        );
    }

    private function authenticate(): void
    {
        $crawler = $this->client->request('GET', '/clothes');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $this->email,
            '_password' => 'mot-de-passe-e2e',
        ]);
        $this->client->submit($form);
        $this->client->waitFor('.product-grid');
    }

    private function createFixtures(): void
    {
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $now = new \DateTimeImmutable();
        $admin = (new Admin())
            ->setEmail($this->email)
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'mot-de-passe-e2e'));
        $this->category = (new Category())
            ->setName('Catégorie E2E ' . $this->token)
            ->setSlug('categorie-e2e-' . $this->token)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->collection = (new Collections())
            ->setName('Collection E2E ' . $this->token)
            ->setCategory($this->category)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->color = (new Clothescolor())
            ->setName('Bleu E2E ' . $this->token)
            ->setHexa('112233')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->small = $this->findOrCreateSize('S', $now);
        $this->medium = $this->findOrCreateSize('M', $now);

        foreach ([$admin, $this->category, $this->collection, $this->color] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }

    /** @param array<string, int> $sizes */
    private function createClotheWithSizes(array $sizes): Clothes
    {
        $now = new \DateTimeImmutable();
        $clothe = (new Clothes())
            ->setName('Vêtement cycle E2E ' . $this->token)
            ->setPrice(6900)
            ->setCollection($this->collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $slug = 'vetement-cycle-e2e-' . $this->token . '-bleu-e2e-' . $this->token;

        foreach ($sizes as $sizeName => $stock) {
            $size = 'S' === $sizeName ? $this->small : $this->medium;
            $clothe->addVariant((new ClothesVariant())
                ->setName($clothe->getName() . ' ' . $this->color->getName() . ' ' . $sizeName)
                ->setSlug($slug)
                ->setColor($this->color)
                ->setSize($size)
                ->setSku(strtoupper($this->token . '-' . $sizeName))
                ->setStock($stock)
                ->setDescription('Description E2E')
                ->setMetadescription('Méta-description E2E')
                ->setImages(['/images/test-' . $this->token . '.png'])
                ->setPublicationStatus(ClotheStatus::Draft)
                ->setCreatedAt($now)
                ->setEditedAt($now));
        }

        $this->entityManager->persist($clothe);
        $this->entityManager->flush();

        return $clothe;
    }

    private function openSizesModal(Clothes $clothe): void
    {
        $variant = $clothe->getVariants()->first();
        self::assertInstanceOf(ClothesVariant::class, $variant, 'Blocage de préparation : aucune variante source.');
        $this->client->request('GET', '/clothes/' . $variant->getSlug());
        $this->client->waitFor('#clothe-show-title');
        $this->client->getWebDriver()->findElement(WebDriverBy::id('clothe-tab-variants'))->click();
        $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('#clothe-panel-variants a[data-turbo-stream]'),
        )->click();
        $this->client->waitFor('#clothe-sizes-modal-title');
    }

    private function sizeCheckbox(string $size): \Facebook\WebDriver\Remote\RemoteWebElement
    {
        return $this->client->getWebDriver()->findElement(WebDriverBy::cssSelector(sprintf(
            '#modal-root input[name="sizes[]"][value="%s"]',
            $size,
        )));
    }

    private function modalSubmitButton(): \Facebook\WebDriver\Remote\RemoteWebElement
    {
        return $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('#modal-root button[type="submit"]'),
        );
    }

    private function submitSizesModal(): void
    {
        $modalForm = $this->client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('#modal-root form'),
        );
        $this->modalSubmitButton()->click();
        $this->client->getWebDriver()->wait()->until(
            WebDriverExpectedCondition::stalenessOf($modalForm),
        );
    }

    private function findVariant(Clothes $clothe, string $size): ?ClothesVariant
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(ClothesVariant::class)->createQueryBuilder('variant')
            ->join('variant.size', 'size')
            ->andWhere('variant.clothes = :clothe')
            ->andWhere('size.name = :size')
            ->setParameter('clothe', $clothe->getId())
            ->setParameter('size', $size)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findOrCreateSize(string $name, \DateTimeImmutable $now): Clothessize
    {
        $size = $this->entityManager->getRepository(Clothessize::class)->findOneBy(['name' => $name]);
        if ($size instanceof Clothessize) {
            return $size;
        }

        $size = (new Clothessize())->setName($name)->setCreatedAt($now)->setEditedAt($now);
        $this->entityManager->persist($size);

        return $size;
    }

    private function createTemporaryPng(): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'clothes-e2e-');
        if (false === $temporaryPath) {
            self::fail('Blocage de préparation : impossible de créer l’image PNG temporaire.');
        }
        $path = $temporaryPath . '.png';
        if (!rename($temporaryPath, $path)) {
            self::fail('Blocage de préparation : impossible de nommer l’image PNG temporaire.');
        }
        file_put_contents(
            $path,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
                true,
            ),
        );

        return $path;
    }

    /** @return array{0: array<string, string>, 1: array<never>, 2: array<string, string>} */
    private function pantherOptions(): array
    {
        $seleniumHost = (string) ($_SERVER['PANTHER_SELENIUM_HOST'] ?? '');
        if ('' === $seleniumHost) {
            return [['router' => 'index.php'], [], []];
        }

        return [[
            'browser' => self::SELENIUM,
            'external_base_uri' => (string) $_SERVER['PANTHER_EXTERNAL_BASE_URI'],
        ], [], ['host' => $seleniumHost]];
    }

    private function assertTestDatabase(): void
    {
        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        self::assertStringEndsWith(
            '_test',
            $databaseName,
            sprintf('Sécurité : refus d’exécuter Panther sur la base « %s ».', $databaseName),
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager, $this->token)) {
            $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $connection = $this->entityManager->getConnection();
            $parameters = ['token' => '%' . $this->token . '%'];
            $connection->executeStatement('DELETE FROM clothes_variant WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM clothes WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM clothescolor WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM collections WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM category WHERE name LIKE :token', $parameters);
            $connection->executeStatement(
                'DELETE FROM admin WHERE email = :email',
                ['email' => $this->email],
            );
        }
        if (isset($this->sourceImage) && is_file($this->sourceImage)) {
            unlink($this->sourceImage);
        }
        if (null !== $this->localUploadDirectory && is_dir($this->localUploadDirectory)) {
            foreach (glob($this->localUploadDirectory . '/*') ?: [] as $file) {
                is_file($file) && unlink($file);
            }
            @rmdir($this->localUploadDirectory);
        }

        parent::tearDown();
    }
}
