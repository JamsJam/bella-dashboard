<?php

namespace App\Application\ImageDeformation;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImageDeformationJobStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function createDirectory(string $jobId): string
    {
        $directory = $this->directory($jobId);
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier de traitement.');
        }

        return $directory;
    }

    public function sourcePath(string $jobId): string
    {
        return $this->directory($jobId) . '/source.png';
    }

    public function resultPath(string $jobId): string
    {
        return $this->directory($jobId) . '/result.png';
    }

    /** @return array{status: string, error?: string}|null */
    public function readStatus(string $jobId): ?array
    {
        $path = $this->directory($jobId) . '/status.json';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (false === $contents) {
            return null;
        }

        $status = json_decode($contents, true);

        return is_array($status) && isset($status['status']) ? $status : null;
    }

    public function writeStatus(string $jobId, string $status, ?string $error = null): void
    {
        $payload = ['status' => $status];
        if (null !== $error) {
            $payload['error'] = substr($error, 0, 1000);
        }

        $statusPath = $this->directory($jobId) . '/status.json';
        $temporaryPath = $statusPath . '.tmp';
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (false === file_put_contents($temporaryPath, $json, LOCK_EX) || !rename($temporaryPath, $statusPath)) {
            @unlink($temporaryPath);
            throw new \RuntimeException('Impossible d’enregistrer le statut du traitement.');
        }
    }

    public function cleanupAll(): void
    {
        $root = $this->rootDirectory();
        if (!is_dir($root)) {
            return;
        }

        foreach (new \DirectoryIterator($root) as $directory) {
            if ($directory->isDot() || !$directory->isDir() || !preg_match('/^[a-f0-9]{32}$/', $directory->getFilename())) {
                continue;
            }
            $status = $this->readStatus($directory->getFilename());
            if (in_array($status['status'] ?? null, ['pending', 'processing'], true)) {
                continue;
            }

            $this->delete($directory->getFilename());
        }
    }

    public function delete(string $jobId): void
    {
        $directory = $this->directory($jobId);
        foreach (['source.png', 'crop.png', 'result.png', 'status.json', 'status.json.tmp'] as $filename) {
            $path = $directory . '/' . $filename;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }

    private function directory(string $jobId): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $jobId)) {
            throw new \InvalidArgumentException('Identifiant de traitement invalide.');
        }

        return $this->rootDirectory() . '/' . $jobId;
    }

    private function rootDirectory(): string
    {
        return $this->projectDir . '/var/image-deformation';
    }
}
