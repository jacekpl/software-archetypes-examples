<?php

namespace App\Entity\Party;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'identifier_type')]
class IdentifierType
{
    #[ORM\Id]
    #[ORM\Column(length: 30)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pattern = null;

    #[ORM\Column(type: 'boolean')]
    private bool $appliesToPerson;

    #[ORM\Column(type: 'boolean')]
    private bool $appliesToOrganization;

    #[ORM\Column(type: 'boolean')]
    private bool $requiresVerification = false;

    public function __construct(
        string $code,
        string $name,
        bool $appliesToPerson,
        bool $appliesToOrganization,
        ?string $pattern = null,
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->appliesToPerson = $appliesToPerson;
        $this->appliesToOrganization = $appliesToOrganization;
        $this->pattern = $pattern;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function validate(string $value): bool
    {
        if ($this->pattern === null) {
            return true;
        }
        return (bool) preg_match('/' . $this->pattern . '/', $value);
    }

    public function canApplyTo(Party $party): bool
    {
        if ($party instanceof Person) {
            return $this->appliesToPerson;
        }
        if ($party instanceof Organization) {
            return $this->appliesToOrganization;
        }
        return false;
    }

    public function isRequiresVerification(): bool
    {
        return $this->requiresVerification;
    }

    public function setRequiresVerification(bool $requiresVerification): void
    {
        $this->requiresVerification = $requiresVerification;
    }
}
