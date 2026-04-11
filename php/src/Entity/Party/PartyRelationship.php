<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'party_relationship')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'relationship_type', type: 'string', length: 30)]
#[ORM\DiscriminatorMap([
    'employment' => Employment::class,
    'ownership' => Ownership::class,
])]
abstract class PartyRelationship
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Party::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Party $fromParty;

    #[ORM\ManyToOne(targetEntity: Party::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Party $toParty;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $validFrom;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Party $fromParty, Party $toParty, \DateTimeImmutable $validFrom)
    {
        if ($fromParty->getId()->equals($toParty->getId())) {
            throw new \DomainException('Self-relationships not allowed');
        }
        $this->id = Uuid::v7();
        $this->fromParty = $fromParty;
        $this->toParty = $toParty;
        $this->validFrom = $validFrom;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFromParty(): Party
    {
        return $this->fromParty;
    }

    public function getToParty(): Party
    {
        return $this->toParty;
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTimeImmutable
    {
        return $this->validTo;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function isActive(): bool
    {
        $today = new \DateTimeImmutable('today');
        return $this->validFrom <= $today && ($this->validTo === null || $this->validTo >= $today);
    }

    public function terminate(\DateTimeImmutable $endDate, string $reason): void
    {
        if ($endDate < $this->validFrom) {
            throw new \DomainException('End date cannot be before start date');
        }
        if ($this->validTo !== null) {
            throw new \DomainException('Relationship already terminated');
        }
        $this->validTo = $endDate;
        $this->notes = $reason;
    }

    public function getOtherParty(Party $party): Party
    {
        if ($this->fromParty->getId()->equals($party->getId())) {
            return $this->toParty;
        }
        if ($this->toParty->getId()->equals($party->getId())) {
            return $this->fromParty;
        }
        throw new \DomainException('Party not in relationship');
    }
}
