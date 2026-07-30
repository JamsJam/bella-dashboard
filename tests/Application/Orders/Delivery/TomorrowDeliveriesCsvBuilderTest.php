<?php

namespace App\Tests\Application\Orders\Delivery;

use App\Entity\Orders\Orders;
use App\Scheduler\Task\TomorrowDeliveries\TomorrowDeliveriesCsvBuilder;
use PHPUnit\Framework\TestCase;

final class TomorrowDeliveriesCsvBuilderTest extends TestCase
{
    public function testItBuildsDeliveryCsvWithCustomerDetails(): void
    {
        $order = (new Orders())
            ->setOrderReference('ORDER-20260730-AB12CD34')
            ->setShippinfo([
                'firstName' => 'Jeanne',
                'lastName' => 'Martin',
                'phone' => '0690 00 00 00',
                'address' => '12 rue des Fleurs',
                'postcode' => '97110',
                'city' => 'Pointe-à-Pitre',
                'destination' => 'Guadeloupe',
            ]);

        $csv = (new TomorrowDeliveriesCsvBuilder())->build([$order]);

        self::assertStringContainsString(
            'Commande;Nom;Prénom;Téléphone;"Adresse de livraison"',
            $csv,
        );
        self::assertStringContainsString('ORDER-20260730-AB12CD34', $csv);
        self::assertStringContainsString('Martin;Jeanne;"0690 00 00 00"', $csv);
        self::assertStringContainsString(
            '"12 rue des Fleurs, 97110 Pointe-à-Pitre, Guadeloupe"',
            $csv,
        );
    }

    public function testItUsesExplicitFallbacksForMissingDetails(): void
    {
        $order = (new Orders())
            ->setOrderReference('ORDER-20260730-EF56GH78')
            ->setShippinfo([]);

        $csv = (new TomorrowDeliveriesCsvBuilder())->build([$order]);

        self::assertStringContainsString(
            'ORDER-20260730-EF56GH78;"Non renseigné";"Non renseigné";"Non renseigné";"Non renseignée"',
            $csv,
        );
    }
}
