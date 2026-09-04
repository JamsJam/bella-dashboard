<?php

namespace App\Tests\Avatar\Integration;

use App\Application\Avatar\Services\AvatarValidatedFilterValueService;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameWorkflow;
use App\Entity\Avatar\Noses\Nose;
use App\Entity\Avatar\Noses\Noseshape;
use App\Entity\Avatar\Skincolor;
use App\Entity\AvatarTemp;
use App\Message\Avatar\RenameAvatarMessage;
use App\MessageHandler\Avatar\RenameAvatarMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Workflow\WorkflowInterface;

#[Group('integration')]
#[Group('avatar')]
final class AvatarRenameProcessTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private string $temporaryDirectory;
    private string $destinationDirectory;
    private string $token;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        if (!str_ends_with($databaseName, '_test')) {
            self::fail(sprintf('Sécurité : refus d’exécuter le test Avatar sur la base « %s ».', $databaseName));
        }

        $this->token = 'it_' . bin2hex(random_bytes(5));
        $projectDirectory = (string) self::getContainer()->getParameter('kernel.project_dir');
        $this->temporaryDirectory = $projectDirectory . '/var/avatar-temp/' . $this->token;
        $this->destinationDirectory = $projectDirectory . '/public/images/upload/avatar/nose/' . $this->token . '_skin/' . $this->token . '_shape';
    }

    public function testCompleteRenameWorkflowCreatesTheAvatarAndItsFile(): void
    {
        mkdir($this->temporaryDirectory, 0775, true);
        $sourcePath = $this->temporaryDirectory . '/source.png';
        file_put_contents($sourcePath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        ));
        $sourceChecksum = hash_file('sha256', $sourcePath);
        self::assertIsString($sourceChecksum);

        $avatarTemp = (new AvatarTemp())
            ->setOriginalName('source.png')
            ->setStoredName('source.png')
            ->setRelativePath('source.png')
            ->setTempPath($sourcePath)
            ->setMimeType('image/png')
            ->setFileSize((int) filesize($sourcePath))
            ->setExtension('png');
        $this->entityManager->persist($avatarTemp);
        $this->entityManager->flush();
        $avatarTempId = $avatarTemp->getId();

        $newName = sprintf('nose__%s_skin__%s_shape.png', $this->token, $this->token);
        $filters = [
            'skinColor' => ['name' => $this->token . '_skin', 'hexa' => '#112233'],
            'shape' => $this->token . '_shape',
        ];

        $workflow = self::getContainer()->get('state_machine.avatar_rename');
        $contextStore = self::getContainer()->get(AvatarRenameGuardContextStore::class);
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        $validation = new AvatarRenameValidationContext($newName, 'nose', $filters, false);
        $contextStore->setValidation($avatarTemp, $validation);
        try {
            $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_VALIDATE);
        } finally {
            $contextStore->clearValidation($avatarTemp);
        }

        self::getContainer()->get(AvatarValidatedFilterValueService::class)->persistNewValues('nose', $filters);
        $avatarTemp->setFinalName($newName);
        $this->entityManager->flush();
        self::assertSame(AvatarRenameWorkflow::PLACE_VALIDATED, $avatarTemp->getStatus());

        $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_START_RENAMING);
        $this->entityManager->flush();

        self::getContainer()->get(MessageBusInterface::class)->dispatch(new RenameAvatarMessage((int) $avatarTempId));
        $transport = self::getContainer()->get('messenger.transport.avatar_rename');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(1, $transport->getSent());

        $message = $transport->getSent()[0]->getMessage();
        self::assertInstanceOf(RenameAvatarMessage::class, $message);
        self::getContainer()->get(RenameAvatarMessageHandler::class)($message);

        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(AvatarTemp::class, $avatarTempId));

        $nose = $this->entityManager->getRepository(Nose::class)->findOneBy(['name' => pathinfo($newName, PATHINFO_FILENAME)]);
        self::assertInstanceOf(Nose::class, $nose);
        self::assertSame($this->token . '_skin', $nose->getSkincolor()?->getName());
        self::assertSame('112233', $nose->getSkincolor()?->getHexa());
        self::assertSame($this->token . '_shape', $nose->getShape()?->getName());

        $destinationPath = $this->destinationDirectory . '/' . $newName;
        self::assertFileExists($destinationPath);
        self::assertSame($sourceChecksum, hash_file('sha256', $destinationPath));
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->isOpen() && isset($this->token)) {
            $nose = $this->entityManager->getRepository(Nose::class)->findOneBy(['name' => sprintf('nose__%s_skin__%s_shape', $this->token, $this->token)]);
            if ($nose instanceof Nose) {
                $this->entityManager->remove($nose);
                $this->entityManager->flush();
            }

            foreach (
                [
                    [Skincolor::class, $this->token . '_skin'],
                    [Noseshape::class, $this->token . '_shape'],
                ] as [$class, $name]
            ) {
                $entity = $this->entityManager->getRepository($class)->findOneBy(['name' => $name]);
                if (is_object($entity)) {
                    $this->entityManager->remove($entity);
                }
            }
            $this->entityManager->flush();
        }

        $this->removeFileAndEmptyParents($this->destinationDirectory . '/nose__' . ($this->token ?? '') . '_skin__' . ($this->token ?? '') . '_shape.png', 4);
        $this->removeFileAndEmptyParents(($this->temporaryDirectory ?? '') . '/source.png', 2);
        parent::tearDown();
    }

    private function removeFileAndEmptyParents(string $path, int $levels): void
    {
        if (is_file($path)) {
            unlink($path);
        }

        $directory = dirname($path);
        for ($level = 0; $level < $levels && is_dir($directory); ++$level) {
            if (!@rmdir($directory)) {
                break;
            }
            $directory = dirname($directory);
        }
    }
}
