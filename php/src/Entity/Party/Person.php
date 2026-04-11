<?php

namespace App\Entity\Party;

use App\Enum\Gender;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Person extends Party
{
    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: Gender::class)]
    private ?Gender $gender = null;

    public function __construct(string $firstName, string $lastName)
    {
        parent::__construct();
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getName(): string
    {
        if ($this->middleName) {
            return "{$this->firstName} {$this->middleName} {$this->lastName}";
        }
        return "{$this->firstName} {$this->lastName}";
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): void
    {
        $this->gender = $gender;
    }

    public function getAge(): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }
        return $this->birthDate->diff(new \DateTimeImmutable())->y;
    }

    public function isAdult(): bool
    {
        $age = $this->getAge();
        return $age !== null && $age >= 18;
    }
}
