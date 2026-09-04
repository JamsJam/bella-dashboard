<?php

namespace App\Application\Config\Service;

final readonly class SiteTimezone
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(private GeneralConfigService $generalConfigService)
    {
    }

    public function timezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->generalConfigService->get()->timezone);
    }

    public function name(): string
    {
        return $this->timezone()->getName();
    }

    public function nowLocal(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone());
    }

    public function nowUtc(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
    }

    public function localInputToUtc(string $value): \DateTimeImmutable
    {
        $localDate = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $this->timezone());
        $errors = \DateTimeImmutable::getLastErrors();

        if (
            !$localDate instanceof \DateTimeImmutable
            || (is_array($errors) && (0 !== $errors['warning_count'] || 0 !== $errors['error_count']))
            || $localDate->format('Y-m-d\TH:i') !== $value
        ) {
            throw new \DomainException('Choisissez une date et une heure valides.');
        }

        return $localDate->setTimezone(new \DateTimeZone(self::STORAGE_TIMEZONE));
    }
}
