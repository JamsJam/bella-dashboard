<?php

namespace App\Tests\Application\Avatar\Resolver;

use App\Application\Avatar\Resolver\AvatarEntityResolver;
use PHPUnit\Framework\TestCase;

final class AvatarEntityResolverTest extends TestCase
{
    private AvatarEntityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AvatarEntityResolver();
    }

    /**
     * Test that AvatarEntityResolver resolves a valid 'body' key to the correct entity class
     */
    public function testResolvesBodyKey(): void
    {
        echo "Testing: Resolves body key to Body entity class\n";
        $result = $this->resolver->resolve('body');
        $this->assertSame(\App\Entity\Avatar\Body\Body::class, $result, 'Failed to resolve body key to correct entity class');
    }

    /**
     * Test that AvatarEntityResolver throws InvalidArgumentException for an invalid key
     */
    public function testThrowsExceptionForInvalidKey(): void
    {
        echo "Testing: Throws exception for invalid key\n";
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown key "invalid"');
        $this->resolver->resolve('invalid');
    }

    /**
     * Test that AvatarEntityResolver returns all available parts
     */
    public function testGetAvailableParts(): void
    {
        echo "Testing: Returns all available parts\n";
        $parts = $this->resolver->getAvailableParts();
        $expectedParts = ['body', 'eyebrows', 'eyes', 'face', 'hair', 'mouth', 'nose'];
        
        $this->assertCount(7, $parts, 'Failed: Expected 7 available parts');
        $this->assertEqualsCanonicalizing($expectedParts, $parts, 'Failed: Available parts do not match expected parts');
    }

    /**
     * Test that AvatarEntityResolver supports valid keys
     */
    public function testSupportsValidKey(): void
    {
        echo "Testing: Supports valid keys\n";
        $this->assertTrue($this->resolver->supports('body'), 'Failed: Resolver should support body key');

    }

    /**
     * Test that AvatarEntityResolver does not support invalid keys
     */
    public function testDoesNotSupportInvalidKey(): void
    {
        echo "Testing: Does not support invalid keys\n";
        $this->assertFalse($this->resolver->supports('invalid'), 'Failed: Resolver should not support invalid key');
        $this->assertFalse($this->resolver->supports(''), 'Failed: Resolver should not support empty key');
        $this->assertFalse($this->resolver->supports('unknown_part'), 'Failed: Resolver should not support unknown_part key');
    }
}
