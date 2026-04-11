<?php

namespace App\Entity\Commerce;

use App\Embeddable\Money;
use App\Entity\Party\Customer;
use App\Entity\Product\Product;
use App\Enum\OrderStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 30, unique: true)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $orderDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(length: 3)]
    private string $currency;

    #[ORM\Column(type: 'json')]
    private array $billingAddress;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'subtotal_')]
    private Money $subtotal;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'discount_total_')]
    private Money $discountTotal;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'tax_total_')]
    private Money $taxTotal;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'shipping_cost_')]
    private Money $shippingCost;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'grand_total_')]
    private Money $grandTotal;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cancellationReason = null;

    /** @var Collection<int, OrderLine> */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['lineNumber' => 'ASC'])]
    private Collection $lines;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $orderNumber, Customer $customer, string $currency, array $billingAddress)
    {
        $this->id = Uuid::v7();
        $this->orderNumber = $orderNumber;
        $this->customer = $customer;
        $this->currency = $currency;
        $this->billingAddress = $billingAddress;
        $this->status = OrderStatus::Draft;
        $this->orderDate = new \DateTimeImmutable();
        $this->subtotal = Money::zero($currency);
        $this->discountTotal = Money::zero($currency);
        $this->taxTotal = Money::zero($currency);
        $this->shippingCost = Money::zero($currency);
        $this->grandTotal = Money::zero($currency);
        $this->lines = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getSubtotal(): Money
    {
        return $this->subtotal;
    }

    public function getTaxTotal(): Money
    {
        return $this->taxTotal;
    }

    public function getGrandTotal(): Money
    {
        return $this->grandTotal;
    }

    /** @return Collection<int, OrderLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $address): void
    {
        $this->shippingAddress = $address;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function addLine(Product $product, int $quantity, Money $unitPrice): OrderLine
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException('Cannot modify confirmed order');
        }
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be positive');
        }

        $line = new OrderLine(
            $this,
            $this->lines->count() + 1,
            $product,
            $quantity,
            $unitPrice,
            $product->getTaxRate(),
        );
        $this->lines->add($line);
        $this->recalculateTotals();
        return $line;
    }

    public function recalculateTotals(): void
    {
        $subtotal = 0;
        $tax = 0;
        foreach ($this->lines as $line) {
            $subtotal += $line->getLineTotal()->getCents();
            $tax += $line->getTaxAmount()->getCents();
        }
        $this->subtotal = new Money($subtotal, $this->currency);
        $this->taxTotal = new Money($tax, $this->currency);
        $this->grandTotal = new Money(
            $subtotal + $tax + $this->shippingCost->getCents() - $this->discountTotal->getCents(),
            $this->currency
        );
    }

    public function confirm(): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new \DomainException('Can only confirm draft orders');
        }
        if ($this->lines->isEmpty()) {
            throw new \DomainException('Order must have at least one line');
        }
        $this->status = OrderStatus::Confirmed;
        $this->confirmedAt = new \DateTimeImmutable();
    }

    public function markPaid(Payment $payment): void
    {
        if ($this->status !== OrderStatus::Confirmed) {
            throw new \DomainException('Order must be confirmed before payment');
        }
        $this->status = OrderStatus::Paid;
    }

    public function startProcessing(): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new \DomainException('Order must be paid before processing');
        }
        $this->status = OrderStatus::Processing;
    }

    public function markShipped(): void
    {
        if ($this->status !== OrderStatus::Processing) {
            throw new \DomainException('Order must be processing to ship');
        }
        $this->status = OrderStatus::Shipped;
    }

    public function markDelivered(): void
    {
        if ($this->status !== OrderStatus::Shipped) {
            throw new \DomainException('Order must be shipped to deliver');
        }
        $this->status = OrderStatus::Delivered;
    }

    public function complete(): void
    {
        if ($this->status !== OrderStatus::Delivered) {
            throw new \DomainException('Order must be delivered to complete');
        }
        $this->status = OrderStatus::Completed;
    }

    public function cancel(string $reason): void
    {
        if (!in_array($this->status, [OrderStatus::Draft, OrderStatus::Confirmed])) {
            throw new \DomainException("Cannot cancel order in status {$this->status->value}");
        }
        $this->status = OrderStatus::Cancelled;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->cancellationReason = $reason;
    }
}
