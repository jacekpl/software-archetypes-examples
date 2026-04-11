<?php

namespace App\Entity\Party;

use App\Enum\EmploymentType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employment')]
class Employment extends PartyRelationship
{
    #[ORM\Column(length: 100)]
    private string $jobTitle;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(type: 'string', length: 20, enumType: EmploymentType::class)]
    private EmploymentType $employmentType;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $workLocation = null;

    public function __construct(
        Person $employee,
        Organization $employer,
        \DateTimeImmutable $validFrom,
        string $jobTitle,
        EmploymentType $employmentType,
    ) {
        parent::__construct($employee, $employer, $validFrom);
        $this->jobTitle = $jobTitle;
        $this->employmentType = $employmentType;
    }

    public function getEmployee(): Person
    {
        /** @var Person */
        return $this->getFromParty();
    }

    public function getEmployer(): Organization
    {
        /** @var Organization */
        return $this->getToParty();
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getEmploymentType(): EmploymentType
    {
        return $this->employmentType;
    }

    public function getWorkLocation(): ?string
    {
        return $this->workLocation;
    }

    public function setWorkLocation(?string $workLocation): void
    {
        $this->workLocation = $workLocation;
    }

    public function promote(string $newTitle, ?string $newDepartment = null): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot promote in terminated employment');
        }
        $this->jobTitle = $newTitle;
        if ($newDepartment !== null) {
            $this->department = $newDepartment;
        }
    }
}
