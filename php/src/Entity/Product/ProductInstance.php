<?php

namespace App\Entity\Product;

use App\Enum\InstanceStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'product_instance')]
class ProductInstance
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'instances')]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(length: 100, unique: true, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $batchNumber = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $manufacturedDate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    #[ORM\Column(type: 'string', length: 20, enumType: InstanceStatus::class)]
    private InstanceStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Product $product)
    {
        $this->id = Uuid::v7();
        $this->product = $product;
        $this->status = InstanceStatus::Available;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    public function setBatchNumber(?string $batchNumber): void
    {
        $this->batchNumber = $batchNumber;
    }

    public function setManufacturedDate(?\DateTimeImmutable $date): void
    {
        $this->manufacturedDate = $date;
    }

    public function setExpiryDate(?\DateTimeImmutable $date): void
    {
        $this->expiryDate = $date;
    }

    public function getStatus(): InstanceStatus
    {
        return $this->status;
    }

    public function isAvailable(): bool
    {
        return $this->status === InstanceStatus::Available;
    }

    public function isExpired(): bool
    {
        return $this->expiryDate !== null && $this->expiryDate < new \DateTimeImmutable('today');
    }
}
