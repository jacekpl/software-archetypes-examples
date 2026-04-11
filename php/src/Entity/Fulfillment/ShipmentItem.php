<?php

namespace App\Entity\Fulfillment;

use App\Entity\Commerce\OrderLine;
use App\Entity\Product\Product;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shipment_item')]
class ShipmentItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Shipment::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Shipment $shipment;

    #[ORM\ManyToOne(targetEntity: OrderLine::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?OrderLine $orderLine = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $batchNumber = null;

    public function __construct(Shipment $shipment, Product $product, int $quantity)
    {
        $this->id = Uuid::v7();
        $this->shipment = $shipment;
        $this->product = $product;
        $this->quantity = $quantity;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setOrderLine(?OrderLine $orderLine): void
    {
        $this->orderLine = $orderLine;
    }

    public function setSerialNumber(?string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    public function setBatchNumber(?string $batchNumber): void
    {
        $this->batchNumber = $batchNumber;
    }
}
