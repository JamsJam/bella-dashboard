<?php

namespace App\Tests\Service;

use App\Service\BreadscrumbsService;
use PHPUnit\Framework\TestCase;

class BreadscrumbsServiceTest extends TestCase
{
    private BreadscrumbsService $service;

    protected function setUp(): void
    {
        $this->service = new BreadscrumbsService();
    }

    public function testResolveDashboardRoute(): void
    {
        $result = $this->service->resolve('app_dashboard');

        self::assertSame([
            [
                'label' => 'Dashboard',
                'route' => 'app_dashboard',
            ],
        ], $result);
    }

    public function testResolveUserRoute(): void
    {
        $result = $this->service->resolve('app_user_index');

        self::assertSame([
            [
                'label' => 'Dashboard',
                'route' => 'app_dashboard',
            ],
            [
                'label' => 'Utilisateurs',
                'route' => 'app_user',
            ],
            [
                'label' => 'Index',
                'route' => 'app_user_index',
            ],
        ], $result);
    }

    public function testResolveOrderPendingRoute(): void
    {
        $result = $this->service->resolve('app_order_pending');

        self::assertSame([
            [
                'label' => 'Dashboard',
                'route' => 'app_dashboard',
            ],
            [
                'label' => 'Commandes',
                'route' => 'app_order',
            ],
            [
                'label' => 'En attente',
                'route' => 'app_order_pending',
            ],
        ], $result);
    }

    public function testResolveUnknownLabelUsesUcfirstFallback(): void
    {
        $result = $this->service->resolve('app_product_index');

        self::assertSame([
            [
                'label' => 'Dashboard',
                'route' => 'app_dashboard',
            ],
            [
                'label' => 'Product',
                'route' => 'app_product',
            ],
            [
                'label' => 'Index',
                'route' => 'app_product_index',
            ],
        ], $result);
    }

    public function testResolveRouteWithoutAppPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must start with "app_"');

        $this->service->resolve('user_index');
    }

    public function testResolveEmptyRouteThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->resolve('');
    }

    public function testResolveRouteWithEmptySegmentsThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contains an empty segment');

        $this->service->resolve('app_user__index');
    }

    public function testResolveCollectionRoutesThroughClothes(): void
    {
        self::assertSame([
            ['label' => 'Dashboard', 'route' => 'app_dashboard'],
            ['label' => 'Vêtements', 'route' => 'app_clothes'],
            ['label' => 'Collections'],
        ], $this->service->resolve('app_clothe_collection', currentLabel: 'Collections'));

        self::assertSame([
            ['label' => 'Dashboard', 'route' => 'app_dashboard'],
            ['label' => 'Vêtements', 'route' => 'app_clothes'],
            ['label' => 'Collections'],
            ['label' => 'Collection été'],
        ], $this->service->resolve('app_clothes_collection', currentLabel: 'Collection été'));
    }
}
