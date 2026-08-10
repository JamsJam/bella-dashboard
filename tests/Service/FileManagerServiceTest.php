<?php

namespace App\Tests\Service;

use App\Service\FileManagerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FileManagerServiceTest extends TestCase
{
    private string $root;
    private FileManagerService $fileManager;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/file-manager-' . bin2hex(random_bytes(6));
        $this->fileManager = new FileManagerService(new Filesystem());
        $this->fileManager->ensureDirectory($this->root . '/allowed');
    }

    protected function tearDown(): void
    {
        $this->fileManager->remove($this->root);
    }

    public function testItCreatesCopiesMovesAndRemovesFiles(): void
    {
        $source = $this->root . '/allowed/source.txt';
        file_put_contents($source, 'content');

        $copy = $this->root . '/allowed/copy.txt';
        $moved = $this->root . '/allowed/moved.txt';
        $this->fileManager->copy($source, $copy);
        $this->fileManager->move($copy, $moved);

        self::assertTrue($this->fileManager->isFile($source));
        self::assertTrue($this->fileManager->isFile($moved));
        self::assertFalse($this->fileManager->exists($copy));

        $this->fileManager->remove([$source, $moved]);
        self::assertFalse($this->fileManager->exists($source));
        self::assertFalse($this->fileManager->exists($moved));
    }

    public function testItOnlyResolvesFilesInsideTheAllowedRoot(): void
    {
        $inside = $this->root . '/allowed/avatar.png';
        $outside = $this->root . '/outside.png';
        file_put_contents($inside, 'inside');
        file_put_contents($outside, 'outside');

        self::assertSame(realpath($inside), $this->fileManager->resolveFileWithin($inside, $this->root . '/allowed'));
        self::assertNull($this->fileManager->resolveFileWithin($outside, $this->root . '/allowed'));
    }
}
