<?php

namespace App\ApiResource\Pages;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Page\PageProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/page/{page}',
            requirements: ['page' => '[a-zA-Z0-9_-]+'],
            provider: PageProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final class Page
{
}
