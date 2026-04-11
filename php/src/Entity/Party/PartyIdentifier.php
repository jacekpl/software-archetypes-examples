<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'party_identifier')]
class PartyIdentifier
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Party::class, inversedBy: 'identifiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Party $party;

    #[ORM\ManyToOne(targetEntity: IdentifierType::class)]
    #[ORM\JoinColumn(name: 'identifier_type', referencedColumnName: 'code', nullable: false)]
    private IdentifierType $identifierType;

    #[ORM\Column(length: 100)]
    private string $value;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $issuingAuthority = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $verifiedBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Party $party, IdentifierType $identifierType, string $value)
    {
        if (!$identifierType->canApplyTo($party)) {
            throw new \DomainException("{$identifierType->getCode()} cannot be assigned to " . get_class($party));
        }
        if (!$identifierType->validate($value)) {
            throw new \DomainException("Value does not match pattern for {$identifierType->getCode()}");
        }

        $this->id = Uuid::v7();
        $this->party = $party;
        $this->identifierType = $identifierType;
        $this->value = $value;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getIdentifierType(): IdentifierType
    {
        return $this->identifierType;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getIssuingAuthority(): ?string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(?string $issuingAuthority): void
    {
        $this->issuingAuthority = $issuingAuthority;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function setValidTo(?\DateTimeImmutable $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function isActive(): bool
    {
        $today = new \DateTimeImmutable('today');
        $fromOk = $this->validFrom === null || $this->validFrom <= $today;
        $toOk = $this->validTo === null || $this->validTo >= $today;
        return $fromOk && $toOk;
    }

    public function isExpired(): bool
    {
        return $this->validTo !== null && $this->validTo < new \DateTimeImmutable('today');
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function verify(string $verifier): void
    {
        if ($this->verified) {
            throw new \DomainException('Identifier already verified');
        }
        $this->verified = true;
        $this->verifiedAt = new \DateTimeImmutable();
        $this->verifiedBy = $verifier;
    }
}
