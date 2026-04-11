<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organization extends Party
{
    #[ORM\Column(length: 255)]
    private string $legalName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tradingName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $registrationNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taxId = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $foundedDate = null;

    public function __construct(string $legalName)
    {
        parent::__construct();
        $this->legalName = $legalName;
    }

    public function getName(): string
    {
        return $this->tradingName ?? $this->legalName;
    }

    public function getOfficialName(): string
    {
        return $this->legalName;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function getTradingName(): ?string
    {
        return $this->tradingName;
    }

    public function setTradingName(?string $tradingName): void
    {
        $this->tradingName = $tradingName;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): void
    {
        $this->registrationNumber = $registrationNumber;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function setFoundedDate(?\DateTimeImmutable $foundedDate): void
    {
        $this->foundedDate = $foundedDate;
    }
}
