<?php

namespace App\Application\Config\Dto;

final class ContactConfigDto
{
    /**
     * @param array<int, SocialNetworkDto> $ownerSocialNetworks
     */
    public function __construct(
        public string $ownerEmail = '',
        public string $ownerName = '',
        public array $ownerSocialNetworks = [],
        public DeveloperContactDto $developerContact = new DeveloperContactDto(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $socialNetworks = [];
        foreach (($data['owner_social_networks'] ?? []) as $socialNetwork) {
            if (is_array($socialNetwork)) {
                $socialNetworks[] = SocialNetworkDto::fromArray($socialNetwork);
            }
        }

        if ($socialNetworks === []) {
            $socialNetworks[] = new SocialNetworkDto();
        }

        $developerContact = $data['developer_contact'] ?? [];

        return new self(
            ownerEmail: trim((string) ($data['owner_email'] ?? '')),
            ownerName: trim((string) ($data['owner_name'] ?? '')),
            ownerSocialNetworks: $socialNetworks,
            developerContact: DeveloperContactDto::fromArray(is_array($developerContact) ? $developerContact : []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->ownerEmail !== '') {
            $data['owner_email'] = $this->ownerEmail;
        }

        if ($this->ownerName !== '') {
            $data['owner_name'] = $this->ownerName;
        }

        $socialNetworks = array_values(array_filter(
            array_map(
                static fn (SocialNetworkDto $socialNetwork): array => $socialNetwork->toArray(),
                $this->ownerSocialNetworks,
            ),
            static fn (array $socialNetwork): bool => $socialNetwork !== [],
        ));

        if ($socialNetworks !== []) {
            $data['owner_social_networks'] = $socialNetworks;
        }

        $developerContact = $this->developerContact->toArray();
        if ($developerContact !== []) {
            $data['developer_contact'] = $developerContact;
        }

        return $data;
    }
}
