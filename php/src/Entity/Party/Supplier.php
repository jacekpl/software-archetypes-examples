<?php

namespace App\Entity\Party;

use App\Embeddable\Money;
use App\Enum\SupplierRating;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'supplier')]
class Supplier extends PartyRole
{
    #[ORM\Column(length: 30, unique: true)]
    private string $supplierCode;

    #[ORM\Column(type: 'integer')]
    private int $paymentTermsDays = 30;

    #[ORM\Column(type: 'string', nullable: true, enumType: SupplierRating::class)]
    private ?SupplierRating $rating = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'min_order_')]
    private ?Money $minimumOrderValue = null;

    public function __construct(Party $party, \DateTimeImmutable $validFrom, string $supplierCode)
    {
        parent::__construct($party, $validFrom);
        $this->supplierCode = $supplierCode;
    }

    public function getSupplierCode(): string
    {
        return $this->supplierCode;
    }

    public function getPaymentTermsDays(): int
    {
        return $this->paymentTermsDays;
    }

    public function setPaymentTermsDays(int $days): void
    {
        $this->paymentTermsDays = $days;
    }

    public function getRating(): ?SupplierRating
    {
        return $this->rating;
    }

    public function setRating(?SupplierRating $rating): void
    {
        $this->rating = $rating;
    }
}
