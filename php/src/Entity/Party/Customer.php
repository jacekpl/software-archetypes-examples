<?php

namespace App\Entity\Party;

use App\Embeddable\Money;
use App\Entity\Commerce\Order;
use App\Enum\LoyaltyTier;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer')]
class Customer extends PartyRole
{
    #[ORM\Column(length: 30, unique: true)]
    private string $customerNumber;

    #[ORM\Column(type: 'string', nullable: true, enumType: LoyaltyTier::class)]
    private ?LoyaltyTier $loyaltyTier = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'credit_limit_')]
    private ?Money $creditLimit = null;

    /** @var Collection<int, Order> */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer')]
    private Collection $orders;

    public function __construct(Party $party, \DateTimeImmutable $validFrom, string $customerNumber)
    {
        parent::__construct($party, $validFrom);
        $this->customerNumber = $customerNumber;
        $this->orders = new ArrayCollection();
    }

    public function getCustomerNumber(): string
    {
        return $this->customerNumber;
    }

    public function getLoyaltyTier(): ?LoyaltyTier
    {
        return $this->loyaltyTier;
    }

    public function setLoyaltyTier(?LoyaltyTier $loyaltyTier): void
    {
        $this->loyaltyTier = $loyaltyTier;
    }

    public function getCreditLimit(): ?Money
    {
        return $this->creditLimit;
    }

    public function setCreditLimit(?Money $creditLimit): void
    {
        $this->creditLimit = $creditLimit;
    }

    /** @return Collection<int, Order> */
    public function getOrders(): Collection
    {
        return $this->orders;
    }
}
