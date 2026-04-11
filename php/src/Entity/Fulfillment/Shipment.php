<?php

namespace App\Entity\Fulfillment;

use App\Embeddable\Money;
use App\Entity\Commerce\Order;
use App\Entity\Commerce\OrderLine;
use App\Entity\Product\Product;
use App\Enum\ShipmentStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shipment')]
class Shipment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $shipmentNumber;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: Carrier::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Carrier $carrier = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ShipmentStatus::class)]
    private ShipmentStatus $status;

    #[ORM\Column(type: 'json')]
    private array $shipFromAddress;

    #[ORM\Column(type: 'json')]
    private array $shipToAddress;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $weightGrams = null;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'shipping_cost_')]
    private Money $shippingCost;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $proofOfDelivery = null;

    /** @var Collection<int, ShipmentItem> */
    #[ORM\OneToMany(targetEntity: ShipmentItem::class, mappedBy: 'shipment', cascade: ['persist', 'remove'])]
    private Collection $items;

    /** @var Collection<int, ShipmentEvent> */
    #[ORM\OneToMany(targetEntity: ShipmentEvent::class, mappedBy: 'shipment', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['occurredAt' => 'ASC'])]
    private Collection $events;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    public function __construct(string $shipmentNumber, array $shipFromAddress, array $shipToAddress)
    {
        $this->id = Uuid::v7();
        $this->shipmentNumber = $shipmentNumber;
        $this->shipFromAddress = $shipFromAddress;
        $this->shipToAddress = $shipToAddress;
        $this->status = ShipmentStatus::Created;
        $this->items = new ArrayCollection();
        $this->shippingCost = Money::zero('PLN');
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->addEvent(ShipmentStatus::Created, 'Shipment created');
    }

    public static function fromOrder(Order $order, string $shipmentNumber, array $shipFromAddress): self
    {
        $shipment = new self($shipmentNumber, $shipFromAddress, $order->getShippingAddress() ?? []);
        $shipment->order = $order;
        return $shipment;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getShipmentNumber(): string
    {
        return $this->shipmentNumber;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getCarrier(): ?Carrier
    {
        return $this->carrier;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getStatus(): ShipmentStatus
    {
        return $this->status;
    }

    /** @return Collection<int, ShipmentItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @return Collection<int, ShipmentEvent> */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addItem(Product $product, int $quantity): ShipmentItem
    {
        $item = new ShipmentItem($this, $product, $quantity);
        $this->items->add($item);
        return $item;
    }

    public function addItemFromOrderLine(OrderLine $orderLine, int $quantity): ShipmentItem
    {
        $item = $this->addItem($orderLine->getProduct(), $quantity);
        $item->setOrderLine($orderLine);
        return $item;
    }

    public function assignCarrier(Carrier $carrier, string $trackingNumber): void
    {
        $this->carrier = $carrier;
        $this->trackingNumber = $trackingNumber;
    }

    // === Status transitions ===

    public function startPicking(): void
    {
        $this->requireStatus(ShipmentStatus::Created);
        $this->updateStatus(ShipmentStatus::Picking, 'Picking started');
    }

    public function markPacked(): void
    {
        $this->requireStatus(ShipmentStatus::Picking);
        $this->updateStatus(ShipmentStatus::Packed, 'Packed');
    }

    public function markLabelPrinted(): void
    {
        $this->requireStatus(ShipmentStatus::Packed);
        if ($this->carrier === null) {
            throw new \DomainException('Carrier must be assigned before printing label');
        }
        $this->updateStatus(ShipmentStatus::LabelPrinted, 'Label printed');
    }

    public function handOverToCarrier(): void
    {
        $this->requireStatus(ShipmentStatus::LabelPrinted);
        $this->shippedAt = new \DateTimeImmutable();
        $this->updateStatus(ShipmentStatus::HandedOver, 'Handed to ' . $this->carrier->getName());
    }

    public function markInTransit(?string $location = null): void
    {
        if (!in_array($this->status, [ShipmentStatus::HandedOver, ShipmentStatus::InTransit])) {
            throw new \DomainException('Shipment not yet handed to carrier');
        }
        $this->updateStatus(ShipmentStatus::InTransit, 'In transit', $location);
    }

    public function markOutForDelivery(): void
    {
        $this->requireStatus(ShipmentStatus::InTransit);
        $this->updateStatus(ShipmentStatus::OutForDelivery, 'Out for delivery');
    }

    public function markDelivered(?array $proof = null): void
    {
        $this->requireStatus(ShipmentStatus::OutForDelivery);
        $this->deliveredAt = new \DateTimeImmutable();
        $this->proofOfDelivery = $proof;
        $this->updateStatus(ShipmentStatus::Delivered, 'Delivered');
    }

    public function markDeliveryFailed(string $reason): void
    {
        $this->requireStatus(ShipmentStatus::OutForDelivery);
        $this->updateStatus(ShipmentStatus::DeliveryFailed, 'Failed: ' . $reason);
    }

    public function cancel(string $reason): void
    {
        if ($this->status->isFinal()) {
            throw new \DomainException('Cannot cancel shipment in final status');
        }
        $this->updateStatus(ShipmentStatus::Cancelled, 'Cancelled: ' . $reason);
    }

    public function getTrackingUrl(): ?string
    {
        if ($this->carrier === null || $this->trackingNumber === null) {
            return null;
        }
        return $this->carrier->getTrackingUrl($this->trackingNumber);
    }

    public function getTransitDays(): ?int
    {
        if ($this->shippedAt === null || $this->deliveredAt === null) {
            return null;
        }
        return $this->shippedAt->diff($this->deliveredAt)->days;
    }

    // === Helpers ===

    private function requireStatus(ShipmentStatus $expected): void
    {
        if ($this->status !== $expected) {
            throw new \DomainException("Expected status {$expected->value}, got {$this->status->value}");
        }
    }

    private function updateStatus(ShipmentStatus $newStatus, string $description, ?string $location = null): void
    {
        $this->status = $newStatus;
        $this->addEvent($newStatus, $description, $location);
    }

    private function addEvent(ShipmentStatus $status, string $description, ?string $location = null): ShipmentEvent
    {
        $event = new ShipmentEvent($this, $status, $description, $location);
        $this->events->add($event);
        return $event;
    }
}
