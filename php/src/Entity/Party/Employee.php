<?php

namespace App\Entity\Party;

use App\Embeddable\Money;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'employee')]
class Employee extends PartyRole
{
    #[ORM\Column(length: 30, unique: true)]
    private string $employeeId;

    #[ORM\Column(length: 100)]
    private string $department;

    #[ORM\Column(length: 100)]
    private string $position;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $hireDate;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'salary_')]
    private ?Money $salary = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $manager = null;

    public function __construct(
        Party $party,
        \DateTimeImmutable $validFrom,
        string $employeeId,
        string $department,
        string $position,
        \DateTimeImmutable $hireDate,
    ) {
        parent::__construct($party, $validFrom);
        $this->employeeId = $employeeId;
        $this->department = $department;
        $this->position = $position;
        $this->hireDate = $hireDate;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getHireDate(): \DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function getSalary(): ?Money
    {
        return $this->salary;
    }

    public function setSalary(?Money $salary): void
    {
        $this->salary = $salary;
    }

    public function getManager(): ?self
    {
        return $this->manager;
    }

    public function setManager(?self $manager): void
    {
        $this->manager = $manager;
    }

    public function promote(string $newPosition, Money $newSalary): void
    {
        if (!$this->isActive()) {
            throw new \DomainException('Cannot promote terminated employee');
        }
        if ($this->salary !== null && $newSalary->getCents() < $this->salary->getCents()) {
            throw new \DomainException('Salary cannot decrease on promotion');
        }
        $this->position = $newPosition;
        $this->salary = $newSalary;
    }
}
