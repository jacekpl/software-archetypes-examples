<?php

namespace App\Entity\Commerce;

use App\Embeddable\Money;
use App\Entity\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'order_line')]
#[ORM\UniqueConstraint(columns: ['order_id', 'line_number'])]
class OrderLine
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Order $order;

    #[ORM\Column(type: 'integer')]
    private int $lineNumber;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(length: 50)]
    private string $productSku;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'unit_price_')]
    private Money $unitPrice;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'discount_')]
    private Money $discount;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $taxRate;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'tax_amount_')]
    private Money $taxAmount;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'line_total_')]
    private Money $lineTotal;

    public function __construct(
        Order $order,
        int $lineNumber,
        Product $product,
        int $quantity,
        Money $unitPrice,
        float $taxRate,
    ) {
        $this->id = Uuid::v7();
        $this->order = $order;
        $this->lineNumber = $lineNumber;
        $this->product = $product;
        $this->productName = $product->getName();
        $this->productSku = $product->getSku();
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->taxRate = (string) $taxRate;
        $this->discount = Money::zero($unitPrice->getCurrency());
        $this->recalculate();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUnitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function getLineTotal(): Money
    {
        return $this->lineTotal;
    }

    public function getTaxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function recalculate(): void
    {
        $lineCents = ($this->unitPrice->getCents() * $this->quantity) - $this->discount->getCents();
        $taxCents = (int) round($lineCents * (float) $this->taxRate / 100);
        $currency = $this->unitPrice->getCurrency();

        $this->lineTotal = new Money($lineCents, $currency);
        $this->taxAmount = new Money($taxCents, $currency);
    }
}
