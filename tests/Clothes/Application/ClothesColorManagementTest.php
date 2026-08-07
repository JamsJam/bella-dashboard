<?php

namespace App\Tests\Clothes\Application;

use App\Entity\Clothes\Clothescolor;
use App\Entity\Users\Admin;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Vérifie le bouton d’action, la modale Turbo, le compteur et la suppression protégée. */
#[Group('clothes')]
#[Group('application')]
final class ClothesColorManagementTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;
    private Clothescolor $color;
    private string $token;

    protected function setUp(): void
    {
        $this->token = bin2hex(random_bytes(3));
        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $database = (string) $this->entityManager->getConnection()->getDatabase();
        self::assertStringEndsWith('_test', $database, 'Sécurité : la gestion des couleurs exige la base de test.');

        $now = new \DateTimeImmutable();
        $admin = (new Admin())
            ->setEmail('color-' . $this->token . '@example.test')
            ->setPassword('mot-de-passe-de-test')
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->color = (new Clothescolor())
            ->setName('Turquoise ' . $this->token)
            ->setHexa('12aabb')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->entityManager->persist($admin);
        $this->entityManager->persist($this->color);
        $this->entityManager->flush();
        $this->client->loginUser($admin, 'admin');
    }

    public function testAdministratorOpensModalAndDeletesUnusedColor(): void
    {
        $this->client->request('GET', '/clothes');
        self::assertResponseIsSuccessful('Blocage : l’index des vêtements ne répond pas.');
        self::assertSelectorExists(
            'a[href="/clothes/colors/modal"][data-turbo-stream]',
            'Blocage : l’action Turbo de gestion des couleurs est absente de l’index.',
        );

        $crawler = $this->client->request('GET', '/clothes/colors/modal');
        self::assertResponseIsSuccessful('Blocage : la modale des couleurs ne répond pas.');
        self::assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');
        self::assertSelectorTextContains(
            '#clothes-colors-title',
            'Gestion des couleurs des vêtements',
            'Blocage : le titre de la modale est absent.',
        );
        self::assertSelectorTextContains(
            '.avatar-colors__content small',
            '0 vêtement relié',
            'Blocage : le nombre de vêtements reliés à la couleur est absent.',
        );

        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);
        self::assertResponseIsSuccessful('Blocage : la suppression Turbo de la couleur a échoué.');
        self::assertResponseHeaderSame('Content-Type', 'text/vnd.turbo-stream.html; charset=UTF-8');

        $colorId = $this->color->getId();
        $this->entityManager->clear();
        self::assertNull(
            $this->entityManager->find(Clothescolor::class, $colorId),
            'Blocage : la couleur supprimée depuis la modale existe encore.',
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager, $this->token)) {
            $connection = $this->entityManager->getConnection();
            $connection->executeStatement(
                'DELETE FROM clothescolor WHERE name LIKE :token',
                ['token' => '%' . $this->token . '%'],
            );
            $connection->executeStatement(
                'DELETE FROM admin WHERE email = :email',
                ['email' => 'color-' . $this->token . '@example.test'],
            );
        }

        parent::tearDown();
    }
}
