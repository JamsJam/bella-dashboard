<?php

namespace App\Tests\Avatar\Unit\Resolver;

use App\Application\Avatar\Resolver\AvatarEntityResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie la correspondance entre un identifiant de partie et son entité. */
#[Group('avatar')]
#[Group('unit')]
final class AvatarEntityResolverTest extends TestCase
{
    private AvatarEntityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AvatarEntityResolver();
    }

    /**
     * Test that AvatarEntityResolver resolves a valid 'body' key to the correct entity class.
     */
    public function testResolvesBodyKey(): void
    {
        $result = $this->resolver->resolve('body');
        self::assertSame(\App\Entity\Avatar\Body\Body::class, $result, 'Blocage : la partie body ne pointe plus vers son entité.');
    }

    /**
     * Test that AvatarEntityResolver throws InvalidArgumentException for an invalid key.
     */
    public function testThrowsExceptionForInvalidKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown key "invalid"');
        $this->resolver->resolve('invalid');
    }

    /**
     * Test that AvatarEntityResolver returns all available parts.
     */
    public function testGetAvailableParts(): void
    {
        $parts = $this->resolver->getAvailableParts();
        $expectedParts = ['body', 'eyebrows', 'eyes', 'face', 'accessory', 'hair', 'mouth', 'nose'];

        $this->assertCount(8, $parts, 'Failed: Expected 8 available parts');
        $this->assertEqualsCanonicalizing($expectedParts, $parts, 'Failed: Available parts do not match expected parts');
    }

    /**
     * Test that AvatarEntityResolver supports valid keys.
     */
    public function testSupportsValidKey(): void
    {
        $this->assertTrue($this->resolver->supports('body'), 'Failed: Resolver should support body key');
    }

    public function testResolvesAccessoryToFaceEntity(): void
    {
        $this->assertSame(
            \App\Entity\Avatar\Faces\Faces::class,
            $this->resolver->resolve('accessory'),
        );
    }

    /**
     * Test that AvatarEntityResolver does not support invalid keys.
     */
    public function testDoesNotSupportInvalidKey(): void
    {
        $this->assertFalse($this->resolver->supports('invalid'), 'Failed: Resolver should not support invalid key');
        $this->assertFalse($this->resolver->supports(''), 'Failed: Resolver should not support empty key');
        $this->assertFalse($this->resolver->supports('unknown_part'), 'Failed: Resolver should not support unknown_part key');
    }
}
