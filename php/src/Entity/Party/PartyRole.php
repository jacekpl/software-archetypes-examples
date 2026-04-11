<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'party_role')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'role_type', type: 'string', length: 30)]
#[ORM\DiscriminatorMap([
    'customer' => Customer::class,
    'supplier' => Supplier::class,
    'employee' => Employee::class,
])]
abstract class PartyRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Party::class, inversedBy: 'roles')]
    #[ORM\JoinColumn(nullable: false)]
    private Party $party;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $validFrom;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Party $party, \DateTimeImmutable $validFrom)
    {
        $this->id = Uuid::v7();
        $this->party = $party;
        $this->validFrom = $validFrom;
        $this->createdAt = new \DateTimeImmutable();
        $party->addRole($this);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getParty(): Party
    {
        return $this->party;
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function isActive(): bool
    {
        $today = new \DateTimeImmutable('today');
        return $this->validFrom <= $today && ($this->validTo === null || $this->validTo >= $today);
    }

    public function terminate(\DateTimeImmutable $endDate): void
    {
        if ($endDate < $this->validFrom) {
            throw new \DomainException('End date cannot be before start date');
        }
        if ($this->validTo !== null) {
            throw new \DomainException('Role already terminated');
        }
        $this->validTo = $endDate;
    }
}
