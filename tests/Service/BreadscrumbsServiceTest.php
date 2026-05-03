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
        $result = $this->service->resolve('user_index');

        self::assertSame([
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
}