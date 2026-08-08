<?php

namespace App\Tests\Avatar\EndToEnd;

use App\Entity\Users\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Vérifie le parcours Avatar dans un vrai navigateur avec JavaScript et Turbo. */
#[Group('avatar')]
#[Group('end-to-end')]
final class AvatarNavigationTest extends PantherTestCase
{
    private EntityManagerInterface $entityManager;
    private string $email;

    protected function setUp(): void
    {
        $this->email = 'avatar-e2e-' . bin2hex(random_bytes(5)) . '@example.test';
    }

    public function testAdministratorUsesJavascriptThenNavigatesToAvatarAdditionWithTurbo(): void
    {
        $client = self::createPantherClient(...$this->pantherOptions());
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertTestDatabase();
        $this->createAdministrator();

        // Demander d’abord une page protégée permet aussi de vérifier le retour après connexion.
        $crawler = $client->request('GET', '/avatar');
        self::assertSelectorTextContains(
            '#login-title',
            'Connexion administrateur',
            'Blocage : un visiteur non authentifié n’est pas redirigé vers la connexion.',
        );

        // Ce changement ne peut être vérifié par BrowserKit : le navigateur exécute réellement JavaScript.
        $client->executeScript(
            "localStorage.setItem('theme', 'light'); document.documentElement.dataset.theme = 'light';",
        );
        $client->getWebDriver()->findElement(WebDriverBy::cssSelector('.login-card__theme'))->click();
        self::assertSame(
            'dark',
            $client->executeScript('return document.documentElement.dataset.theme;'),
            'Blocage : le bouton de thème n’exécute plus son comportement JavaScript.',
        );

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => $this->email,
            '_password' => 'mot-de-passe-e2e',
        ]);
        $client->submit($form);
        $client->waitForElementToContain('#avatar-page-title', 'Avatar');

        self::assertStringEndsWith(
            '/avatar',
            $client->getCurrentURL(),
            'Blocage : la connexion ne renvoie pas vers le catalogue Avatar demandé.',
        );

        // Le clic est intercepté par Turbo dans le navigateur et doit remplacer le contenu de la page.
        $client->executeScript("window.__avatarTurboContext = 'same-document';");
        $client->getWebDriver()->findElement(
            WebDriverBy::cssSelector('.tabs a[href="/avatar/add"]'),
        )->click();
        $client->waitForElementToContain('#avatar-page-title', 'Ajouter des avatars');

        self::assertSame(
            'same-document',
            $client->executeScript('return window.__avatarTurboContext ?? null;'),
            'Blocage : Turbo ne conserve pas le contexte JavaScript pendant la navigation Avatar.',
        );

        self::assertStringEndsWith(
            '/avatar/add',
            $client->getCurrentURL(),
            'Blocage : la navigation Turbo n’ouvre pas la page d’ajout Avatar.',
        );
        self::assertSelectorExists(
            '[data-controller~="dropzone"]',
            'Blocage : la dropzone Avatar n’est pas connectée à son contrôleur Stimulus.',
        );
    }

    /**
     * Utilise Selenium dans Docker et ChromeDriver géré par Panther en local.
     *
     * @return array{0: array<string, string>, 1: array<never>, 2: array<string, string>}
     */
    private function pantherOptions(): array
    {
        $seleniumHost = (string) ($_SERVER['PANTHER_SELENIUM_HOST'] ?? '');
        if ('' === $seleniumHost) {
            return [['router' => 'index.php'], [], []];
        }

        return [
            [
                'browser' => self::SELENIUM,
                'external_base_uri' => (string) $_SERVER['PANTHER_EXTERNAL_BASE_URI'],
            ],
            [],
            ['host' => $seleniumHost],
        ];
    }

    private function createAdministrator(): void
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

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
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
        if (isset($this->entityManager, $this->email)) {
            $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
            $this->entityManager->getConnection()->executeStatement(
                'DELETE FROM admin WHERE email = :email',
                ['email' => $this->email],
            );
        }

        parent::tearDown();
    }
}
