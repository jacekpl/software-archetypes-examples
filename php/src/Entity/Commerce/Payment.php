<?php

namespace App\Entity\Commerce;

use App\Embeddable\Money;
use App\Entity\Party\Customer;
use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $reference;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'amount_')]
    private Money $amount;

    #[ORM\Column(type: 'string', length: 30, enumType: PaymentMethod::class)]
    private PaymentMethod $method;

    #[ORM\Column(type: 'string', length: 30, enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(type: 'boolean')]
    private bool $isRefund;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $originalPayment = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'originalPayment')]
    private Collection $refunds;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gatewayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gatewayTransactionId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $gatewayResponse = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $cardLast4 = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $cardBrand = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(
        string $reference,
        Order $order,
        Customer $customer,
        Money $amount,
        PaymentMethod $method,
        bool $isRefund = false,
        ?self $originalPayment = null,
    ) {
        $this->id = Uuid::v7();
        $this->reference = $reference;
        $this->order = $order;
        $this->customer = $customer;
        $this->amount = $amount;
        $this->method = $method;
        $this->status = PaymentStatus::Pending;
        $this->isRefund = $isRefund;
        $this->originalPayment = $originalPayment;
        $this->refunds = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getMethod(): PaymentMethod
    {
        return $this->method;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function isRefund(): bool
    {
        return $this->isRefund;
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [PaymentStatus::Completed, PaymentStatus::PartiallyRefunded]);
    }

    public function markProcessing(): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new \DomainException('Can only process pending payments');
        }
        $this->status = PaymentStatus::Processing;
    }

    public function complete(string $gatewayTransactionId): void
    {
        if ($this->status === PaymentStatus::Completed) {
            return; // Idempotent
        }
        if (!in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Processing])) {
            throw new \DomainException("Cannot complete payment in status {$this->status->value}");
        }
        $this->status = PaymentStatus::Completed;
        $this->completedAt = new \DateTimeImmutable();
        $this->gatewayTransactionId = $gatewayTransactionId;

        if ($this->isRefund && $this->originalPayment !== null) {
            $this->originalPayment->updateRefundStatus();
        }
    }

    public function fail(string $reason): void
    {
        if (!in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Processing])) {
            throw new \DomainException('Cannot fail already completed payment');
        }
        $this->status = PaymentStatus::Failed;
        $this->failureReason = $reason;
    }

    public function cancel(): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new \DomainException('Can only cancel pending payments');
        }
        $this->status = PaymentStatus::Cancelled;
    }

    public function setCardInfo(string $last4, string $brand): void
    {
        if ($this->method !== PaymentMethod::Card) {
            throw new \DomainException('Card info only for card payments');
        }
        $this->cardLast4 = $last4;
        $this->cardBrand = $brand;
    }

    public function setGatewayInfo(string $name, string $transactionId, ?array $response = null): void
    {
        $this->gatewayName = $name;
        $this->gatewayTransactionId = $transactionId;
        $this->gatewayResponse = $response;
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, [PaymentStatus::Completed, PaymentStatus::PartiallyRefunded])
            && !$this->isRefund;
    }

    public function getRefundedAmount(): Money
    {
        $total = 0;
        foreach ($this->refunds as $refund) {
            if ($refund->isSuccessful()) {
                $total += $refund->getAmount()->getCents();
            }
        }
        return new Money($total, $this->amount->getCurrency());
    }

    public function getRefundableAmount(): Money
    {
        return $this->amount->subtract($this->getRefundedAmount());
    }

    public function updateRefundStatus(): void
    {
        $refundable = $this->getRefundableAmount();
        if ($refundable->isZeroOrLess()) {
            $this->status = PaymentStatus::Refunded;
        } else {
            $this->status = PaymentStatus::PartiallyRefunded;
        }
    }

    public static function createRefund(self $originalPayment, Money $refundAmount, string $reference): self
    {
        if (!$originalPayment->canBeRefunded()) {
            throw new \DomainException("Payment in status {$originalPayment->status->value} cannot be refunded");
        }
        if ($refundAmount->getCents() <= 0) {
            throw new \DomainException('Refund amount must be positive');
        }
        if ($refundAmount->isGreaterThan($originalPayment->getRefundableAmount())) {
            throw new \DomainException('Refund exceeds refundable amount');
        }

        return new self(
            $reference,
            $originalPayment->order,
            $originalPayment->customer,
            $refundAmount,
            $originalPayment->method,
            true,
            $originalPayment,
        );
    }

    public function getFormattedAmount(): string
    {
        $prefix = $this->isRefund ? '-' : '';
        return $prefix . $this->amount->format();
    }
}
