<?php

namespace App\Application;

class ServiceLocator
{
    
    private array $namespaces = [
        'App\\Application\\Clothes\\Services\\',
        // "Application\\CV\\Services\\",
        // "Application\\Guestbook\\Services\\",
    ];

    public function resolve(string $serviceName): string
    {
        foreach ($this->namespaces as $ns) {
            $fqcn = $ns.$serviceName;
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        throw new \RuntimeException("Service {$serviceName} introuvable.");
    }
}
