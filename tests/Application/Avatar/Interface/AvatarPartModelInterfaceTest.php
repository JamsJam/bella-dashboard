<?php

namespace App\Tests\Application\Avatar\Interface;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Repository\Avatar\Body\BodyRepository;
use App\Repository\Avatar\Eyebrows\EyebrowsRepository;
use App\Repository\Avatar\Eyes\EyeRepository;
use App\Repository\Avatar\Faces\FacesRepository;
use App\Repository\Avatar\Hairs\HairsRepository;
use App\Repository\Avatar\Mouths\MouthsRepository;
use App\Repository\Avatar\Noses\NoseRepository;
use PHPUnit\Framework\TestCase;

final class AvatarPartModelInterfaceTest extends TestCase
{
    /**
     * Test that all avatar part repositories implement the AvatarPartModelInterface
     */
    public function testAllAvatarPartRepositoriesImplementInterface(): void
    {
        echo "Testing: All avatar part repositories implement AvatarPartModelInterface\n";

        $repositories = [
            BodyRepository::class,
            EyebrowsRepository::class,
            EyeRepository::class,
            FacesRepository::class,
            HairsRepository::class,
            MouthsRepository::class,
            NoseRepository::class,
        ];

        foreach ($repositories as $repositoryClass) {
            $reflection = new \ReflectionClass($repositoryClass);
            $interfaces = $reflection->getInterfaceNames();

            $this->assertContains(
                AvatarPartModelInterface::class,
                $interfaces,
                "Repository {$repositoryClass} does not implement AvatarPartModelInterface"
            );
        }
    }

    /**
     * Test that AvatarPartModelInterface defines the required methods
     */
    public function testInterfaceDefinesRequiredMethods(): void
    {
        echo "Testing: AvatarPartModelInterface defines required methods\n";

        $reflection = new \ReflectionClass(AvatarPartModelInterface::class);
        $methods = $reflection->getMethods();

        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $this->assertContains('findPartByFilters', $methodNames, 'Interface must define findPartByFilters method');
        $this->assertContains('findAllPart', $methodNames, 'Interface must define findAllPart method');
    }

    /**
     * Test that interface methods have correct signatures
     */
    public function testInterfaceMethodsHaveCorrectSignatures(): void
    {
        echo "Testing: Interface methods have correct signatures\n";

        $reflection = new \ReflectionClass(AvatarPartModelInterface::class);

        // Test findPartByFilters method
        $findPartByFiltersMethod = $reflection->getMethod('findPartByFilters');
        $this->assertTrue($findPartByFiltersMethod->isPublic(), 'findPartByFilters must be public');
        $this->assertFalse($findPartByFiltersMethod->isStatic(), 'findPartByFilters must not be static');

        $parameters = $findPartByFiltersMethod->getParameters();
        $this->assertCount(1, $parameters, 'findPartByFilters must have exactly 1 parameter');

        $firstParam = $parameters[0];
        $this->assertEquals('filters', $firstParam->getName(), 'First parameter must be named filters');
        $this->assertEquals('array', $firstParam->getType()->getName(), 'First parameter must be of type array');
        $this->assertTrue($firstParam->isDefaultValueAvailable(), 'First parameter must have a default value');
        $this->assertEquals([], $firstParam->getDefaultValue(), 'First parameter default value must be empty array');

        // Test findAllPart method
        $findAllPartMethod = $reflection->getMethod('findAllPart');
        $this->assertTrue($findAllPartMethod->isPublic(), 'findAllPart must be public');
        $this->assertFalse($findAllPartMethod->isStatic(), 'findAllPart must not be static');

        $parameters = $findAllPartMethod->getParameters();
        $this->assertCount(0, $parameters, 'findAllPart must have no parameters');
    }
}
