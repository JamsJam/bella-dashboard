<?php

namespace App\Tests\Avatar\Unit\Resolver;

use App\Application\Avatar\Resolver\AvatarRepositoryResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie la correspondance entre une partie d’avatar et son dépôt Doctrine. */
#[Group('avatar')]
#[Group('unit')]
final class AvatarRepositoryResolverTest extends TestCase
{
    private AvatarRepositoryResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AvatarRepositoryResolver();
    }

    /**
     * Test that AvatarRepositoryResolver resolves a valid 'body' key to the correct repository class.
     */
    public function testResolvesBodyKey(): void
    {
        $result = $this->resolver->resolve('body');
        $this->assertSame(\App\Repository\Avatar\Body\BodyRepository::class, $result, 'Failed to resolve body key to correct repository class');
    }

    /**
     * Test that AvatarRepositoryResolver throws InvalidArgumentException for an invalid key.
     */
    public function testThrowsExceptionForInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown key "invalid"');
        $this->resolver->resolve('invalid');
    }

    /**
     * Test that AvatarRepositoryResolver returns all available parts.
     */
    public function testGetAvailableParts(): void
    {
        $parts = $this->resolver->getAvailableParts();
        $expectedParts = ['body', 'eyebrows', 'eyes', 'face', 'accessory', 'hair', 'mouth', 'nose'];

        $this->assertCount(8, $parts, 'Failed: Expected 8 available parts');
        $this->assertEqualsCanonicalizing($expectedParts, $parts, 'Failed: Available parts do not match expected parts');
    }

    /**
     * Test that AvatarRepositoryResolver supports valid keys.
     */
    public function testSupportsValidKey(): void
    {
        $this->assertTrue($this->resolver->supports('mouth'), 'Failed: Resolver should support mouth key');
    }

    public function testResolvesAccessoryToFaceRepository(): void
    {
        $this->assertSame(
            \App\Repository\Avatar\Faces\FacesRepository::class,
            $this->resolver->resolve('accessory'),
        );
    }

    /**
     * Test that AvatarRepositoryResolver does not support invalid keys.
     */
    public function testDoesNotSupportInvalidKey(): void
    {
        $this->assertFalse($this->resolver->supports('invalid'), 'Failed: Resolver should not support invalid key');
        $this->assertFalse($this->resolver->supports(''), 'Failed: Resolver should not support empty key');
        $this->assertFalse($this->resolver->supports('unknown_part'), 'Failed: Resolver should not support unknown_part key');
    }
}
