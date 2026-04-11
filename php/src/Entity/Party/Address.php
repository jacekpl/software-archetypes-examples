<?php

namespace App\Entity\Party;

use App\Enum\AddressType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'address')]
class Address
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Party::class, inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Party $party;

    #[ORM\Column(length: 255)]
    private string $street;

    #[ORM\Column(length: 100)]
    private string $city;

    #[ORM\Column(length: 20)]
    private string $postalCode;

    #[ORM\Column(length: 2)]
    private string $country;

    #[ORM\Column(type: 'string', length: 20, enumType: AddressType::class)]
    private AddressType $type;

    #[ORM\Column(type: 'boolean')]
    private bool $isPrimary;

    public function __construct(string $street, string $city, string $postalCode, string $country, AddressType $type, bool $isPrimary = false)
    {
        $this->id = Uuid::v7();
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->country = $country;
        $this->type = $type;
        $this->isPrimary = $isPrimary;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function setParty(Party $party): void
    {
        $this->party = $party;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getType(): AddressType
    {
        return $this->type;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): void
    {
        $this->isPrimary = $isPrimary;
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'postalCode' => $this->postalCode,
            'country' => $this->country,
        ];
    }
}
