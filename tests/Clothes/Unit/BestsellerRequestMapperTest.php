<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\Mapper\BestsellerRequestMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[Group('clothes')]
#[Group('unit')]
final class BestsellerRequestMapperTest extends TestCase
{
    public function testItMapsAndNormalizesAJsonRequest(): void
    {
        $request = Request::create(
            '/clothes/bestsellers',
            'POST',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_X_CSRF_TOKEN' => 'token',
            ],
            content: json_encode([
                'mode' => 'remove',
                'ids' => [4, '4', 0, -2],
                'slug' => 'robe-bleue',
                'pruneOverflow' => true,
            ], JSON_THROW_ON_ERROR),
        );

        $input = (new BestsellerRequestMapper())->map($request);

        self::assertSame([4], $input->ids);
        self::assertSame(['robe-bleue'], $input->slugs);
        self::assertSame('remove', $input->mode);
        self::assertTrue($input->pruneOverflow);
        self::assertSame('token', $input->csrfToken);
        self::assertTrue($input->wantsJson);
        self::assertTrue($input->isXmlHttpRequest);
    }

    public function testItFallsBackToAddForAnUnknownMode(): void
    {
        $request = Request::create('/clothes/bestsellers', 'POST', [
            'mode' => 'unknown',
            'ids' => ['2', 'invalid', '3'],
        ]);

        $input = (new BestsellerRequestMapper())->map($request);

        self::assertSame('add', $input->mode);
        self::assertSame([2, 3], $input->ids);
        self::assertFalse($input->wantsJson);
        self::assertFalse($input->wantsTurboStream);
    }
}
