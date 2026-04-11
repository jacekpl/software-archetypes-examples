<?php

namespace App\Embeddable;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    #[ORM\Column(type: 'integer')]
    private int $cents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    public function __construct(int $cents, string $currency = 'PLN')
    {
        $this->cents = $cents;
        $this->currency = $currency;
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException("Cannot add different currencies: {$this->currency} + {$other->currency}");
        }

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \DomainException("Cannot subtract different currencies");
        }

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function isZeroOrLess(): bool
    {
        return $this->cents <= 0;
    }

    public function format(): string
    {
        return number_format($this->cents / 100, 2, '.', ',') . ' ' . $this->currency;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public static function zero(string $currency = 'PLN'): self
    {
        return new self(0, $currency);
    }
}
