<?php

namespace App\Entity\Fulfillment;

use App\Enum\ShipmentStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shipment_event')]
class ShipmentEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Shipment::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Shipment $shipment;

    #[ORM\Column(type: 'string', length: 20, enumType: ShipmentStatus::class)]
    private ShipmentStatus $status;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $location;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $carrierEventCode = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(Shipment $shipment, ShipmentStatus $status, string $description, ?string $location = null)
    {
        $this->id = Uuid::v7();
        $this->shipment = $shipment;
        $this->status = $status;
        $this->description = $description;
        $this->location = $location;
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): ShipmentStatus
    {
        return $this->status;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setCarrierEventCode(?string $code): void
    {
        $this->carrierEventCode = $code;
    }
}
