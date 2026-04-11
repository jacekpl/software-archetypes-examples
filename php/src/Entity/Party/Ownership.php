<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ownership')]
class Ownership extends PartyRelationship
{
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $ownershipPercentage;

    #[ORM\Column(type: 'boolean')]
    private bool $votingRights;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $shareClass = null;

    public function __construct(
        Party $owner,
        Organization $owned,
        \DateTimeImmutable $validFrom,
        float $ownershipPercentage,
        bool $votingRights = true,
    ) {
        parent::__construct($owner, $owned, $validFrom);
        $this->ownershipPercentage = (string) $ownershipPercentage;
        $this->votingRights = $votingRights;
    }

    public function getOwnershipPercentage(): float
    {
        return (float) $this->ownershipPercentage;
    }

    public function isMajorityOwner(): bool
    {
        return $this->getOwnershipPercentage() > 50.0;
    }

    public function hasVotingRights(): bool
    {
        return $this->votingRights;
    }

    public function getShareClass(): ?string
    {
        return $this->shareClass;
    }

    public function setShareClass(?string $shareClass): void
    {
        $this->shareClass = $shareClass;
    }

    public function adjustOwnership(float $newPercentage): void
    {
        if ($newPercentage < 0 || $newPercentage > 100) {
            throw new \DomainException('Percentage must be between 0 and 100');
        }
        if (!$this->isActive()) {
            throw new \DomainException('Cannot adjust terminated ownership');
        }
        $this->ownershipPercentage = (string) $newPercentage;
    }
}
