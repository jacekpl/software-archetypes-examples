<?php

namespace App\Entity\Party;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'party')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'party_type', type: 'string', length: 20)]
#[ORM\DiscriminatorMap(['person' => Person::class, 'organization' => Organization::class])]
abstract class Party
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    /** @var Collection<int, Address> */
    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'party', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $addresses;

    /** @var Collection<int, PartyRole> */
    #[ORM\OneToMany(targetEntity: PartyRole::class, mappedBy: 'party', cascade: ['persist'])]
    private Collection $roles;

    /** @var Collection<int, PartyIdentifier> */
    #[ORM\OneToMany(targetEntity: PartyIdentifier::class, mappedBy: 'party', cascade: ['persist', 'remove'])]
    private Collection $identifiers;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->addresses = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->identifiers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    abstract public function getName(): string;

    /** @return Collection<int, Address> */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getPrimaryAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isPrimary()) {
                return $address;
            }
        }
        return null;
    }

    public function addAddress(Address $address): void
    {
        if ($address->isPrimary()) {
            foreach ($this->addresses as $existing) {
                if ($existing->getType() === $address->getType()) {
                    $existing->setIsPrimary(false);
                }
            }
        }
        $address->setParty($this);
        $this->addresses->add($address);
    }

    /** @return Collection<int, PartyRole> */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(PartyRole $role): void
    {
        $this->roles->add($role);
    }

    /** @return Collection<int, PartyIdentifier> */
    public function getIdentifiers(): Collection
    {
        return $this->identifiers;
    }

    public function addIdentifier(PartyIdentifier $identifier): void
    {
        $identifier->setParty($this);
        $this->identifiers->add($identifier);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
