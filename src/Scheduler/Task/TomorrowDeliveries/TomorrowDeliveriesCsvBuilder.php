<?php

namespace App\Scheduler\Task\TomorrowDeliveries;

use App\Entity\Orders\Orders;

final class TomorrowDeliveriesCsvBuilder
{
    /**
     * @param list<Orders> $orders
     */
    public function build(array $orders): string
    {
        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new \RuntimeException('Impossible de créer le fichier des livraisons.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv(
            $stream,
            ['Commande', 'Nom', 'Prénom', 'Téléphone', 'Adresse de livraison'],
            ';',
            '"',
            '',
        );

        foreach ($orders as $order) {
            $shipping = $order->getShippinfo();

            fputcsv(
                $stream,
                [
                    $order->getOrderReference() ?? 'Non renseigné',
                    $this->firstValue($shipping, ['lastName', 'lastname', 'last_name', 'name', 'nom']),
                    $this->firstValue($shipping, ['firstName', 'firstname', 'first_name', 'prenom']),
                    $this->firstValue($shipping, ['phone', 'phoneNumber', 'telephone', 'mobile']),
                    $this->address($shipping),
                ],
                ';',
                '"',
                '',
            );
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if (false === $csv) {
            throw new \RuntimeException('Impossible de lire le fichier des livraisons.');
        }

        return $csv;
    }

    /**
     * @param array<string, mixed> $shipping
     * @param list<string>         $keys
     */
    private function firstValue(array $shipping, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($shipping[$key] ?? ''));
            if ('' !== $value) {
                return $value;
            }
        }

        return 'Non renseigné';
    }

    /**
     * @param array<string, mixed> $shipping
     */
    private function address(array $shipping): string
    {
        $parts = array_filter([
            trim((string) ($shipping['address'] ?? '')),
            trim(sprintf(
                '%s %s',
                (string) ($shipping['postcode'] ?? $shipping['postalCode'] ?? ''),
                (string) ($shipping['city'] ?? ''),
            )),
            trim((string) ($shipping['destination'] ?? '')),
        ]);

        return [] === $parts ? 'Non renseignée' : implode(', ', $parts);
    }
}
