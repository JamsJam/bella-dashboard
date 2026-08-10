<?php

namespace App\Tests\Avatar\Unit\Resolver;

use App\Application\Avatar\Resolver\AvatarTemporaryFileResolver;
use App\Entity\AvatarTemp;
use App\Service\FileManagerService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[Group('avatar')]
#[Group('unit')]
final class AvatarTemporaryFileResolverTest extends TestCase
{
    private string $projectDir;
    private string $allowedDirectory;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/avatar-path-resolver-' . bin2hex(random_bytes(6));
        $this->allowedDirectory = $this->projectDir . '/var/avatar-temp/item';
        mkdir($this->allowedDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $path = $this->allowedDirectory . '/avatar.png';
        if (is_file($path)) {
            unlink($path);
        }

        @rmdir($this->allowedDirectory);
        @rmdir(dirname($this->allowedDirectory));
        @rmdir(dirname(dirname($this->allowedDirectory)));
        @rmdir($this->projectDir);
    }

    public function testItResolvesAFileInsideTheTemporaryAvatarDirectory(): void
    {
        $path = $this->allowedDirectory . '/avatar.png';
        file_put_contents($path, 'image');
        $avatarTemp = (new AvatarTemp())->setTempPath($path);

        self::assertSame(realpath($path), $this->resolver()->resolve($avatarTemp));
    }

    public function testItRejectsAFileOutsideTheTemporaryAvatarDirectory(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar-outside-');
        self::assertIsString($path);

        try {
            $avatarTemp = (new AvatarTemp())->setTempPath($path);
            self::assertNull($this->resolver()->resolve($avatarTemp));
        } finally {
            unlink($path);
        }
    }

    private function resolver(): AvatarTemporaryFileResolver
    {
        return new AvatarTemporaryFileResolver(
            $this->projectDir,
            new FileManagerService(new Filesystem()),
        );
    }
}
