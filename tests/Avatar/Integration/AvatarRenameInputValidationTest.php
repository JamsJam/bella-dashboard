<?php

namespace App\Tests\Avatar\Integration;

use App\Application\Avatar\Exception\AvatarInputValidationException;
use App\Application\Avatar\Services\AvatarRenameBatchInputService;
use App\Application\Avatar\Services\AvatarRenameValidationInputService;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('avatar')]
#[Group('integration')]
final class AvatarRenameInputValidationTest extends KernelTestCase
{
    private AvatarRenameValidationInputService $validationInputService;
    private AvatarRenameBatchInputService $batchInputService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validationInputService = self::getContainer()->get(AvatarRenameValidationInputService::class);
        $this->batchInputService = self::getContainer()->get(AvatarRenameBatchInputService::class);
    }

    public function testItAcceptsAValidRenameInput(): void
    {
        $this->validationInputService->prepare(
            name: 'nose__clair__fin.png',
            category: 'nose',
            filtersJson: json_encode(['skinColor' => 'clair', 'shape' => 'fin'], JSON_THROW_ON_ERROR),
        );

        self::addToAssertionCount(1);
    }

    public function testItThrowsOneExceptionContainingEveryViolation(): void
    {
        try {
            $this->validationInputService->prepare(
                name: '../invalid.jpg',
                category: 'nose',
                filtersJson: '{}',
            );
            self::fail('A validation exception was expected.');
        } catch (AvatarInputValidationException $exception) {
            self::assertCount(3, $exception->getViolations());
            self::assertArrayHasKey('name', $exception->errors());
            self::assertArrayHasKey('filters[skinColor]', $exception->errors());
            self::assertArrayHasKey('filters[shape]', $exception->errors());
        }
    }

    public function testItRejectsAnInvalidRenameBatch(): void
    {
        try {
            $this->batchInputService->prepare(json_encode([
                ['avatarTempId' => 0],
                ['unexpected' => 12],
            ], JSON_THROW_ON_ERROR));
            self::fail('A validation exception was expected.');
        } catch (AvatarInputValidationException $exception) {
            self::assertGreaterThanOrEqual(2, count($exception->getViolations()));
            self::assertNotEmpty($exception->errors());
        }
    }

    public function testItReturnsValidatedBatchIdentifiers(): void
    {
        $input = $this->batchInputService->prepare(json_encode([
            ['avatarTempId' => '12'],
            ['avatarTempId' => 24],
        ], JSON_THROW_ON_ERROR));

        self::assertSame([12, 24], $input->avatarTempIds());
    }
}
