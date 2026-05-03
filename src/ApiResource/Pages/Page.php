<?php

Namespace App\ApiResource\Pages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Page\PageProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: "page/{slug}",
            uriVariables: ["slug"],
            provider: PageProvider::class
        ),
    ],
    formats: ['json' => ['application/json']]
)]
final class Page
{
    
    public string $slug; 
    public array $section = []; 
    public array $seo = []; 
}
