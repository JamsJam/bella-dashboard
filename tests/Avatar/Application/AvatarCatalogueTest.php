<?php

namespace App\Tests\Avatar\Application;

use App\Entity\Users\Admin;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Vérifie l’accès HTTP et la structure DOM du catalogue Avatar. */
#[Group('avatar')]
#[Group('application')]
final class AvatarCatalogueTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $email;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->assertTestDatabase();

        $token = bin2hex(random_bytes(5));
        $this->email = 'avatar-' . $token . '@example.test';
        $now = new \DateTimeImmutable();
        $admin = (new Admin())
            ->setEmail($this->email)
            ->setPassword('mot-de-passe-de-test')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($now)
            ->setEditedAt($now);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');
    }

    public function testAdministratorCanOpenTheAvatarCatalogueAndItsActions(): void
    {
        // Cette requête traverse la sécurité, le contrôleur, les dépôts, Twig et DomCrawler.
        $crawler = $this->client->request('GET', '/avatar');

        self::assertResponseIsSuccessful('Blocage : le catalogue Avatar ne répond pas correctement.');
        self::assertSelectorTextContains('h1', 'Avatar', 'Blocage : le titre du catalogue Avatar est absent.');
        self::assertSelectorExists(
            '.product-grid.product-grid--full',
            'Blocage : la grille des parties d’avatar n’est pas rendue dans le DOM.',
        );
        self::assertSelectorExists(
            '#product-grid-catalogue',
            'Blocage : le catalogue des éléments Avatar est absent du DOM.',
        );

        $links = $crawler->filter('.tabs a')->each(static fn ($node): string => trim($node->text()));
        self::assertContains('Ajouter', $links, 'Blocage : l’action d’ajout Avatar n’est plus accessible.');
        self::assertContains('Renommer', $links, 'Blocage : l’action de renommage Avatar n’est plus accessible.');
        self::assertContains('Gérer les couleurs', $links, 'Blocage : la gestion des couleurs Avatar est absente.');
    }

    private function assertTestDatabase(): void
    {
        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        self::assertStringEndsWith(
            '_test',
            $databaseName,
            sprintf('Sécurité : refus d’exécuter le test applicatif Avatar sur la base « %s ».', $databaseName),
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
