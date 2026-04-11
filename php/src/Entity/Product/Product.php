<?php

namespace App\Entity\Product;

use App\Embeddable\Money;
use App\Enum\ProductStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $sku;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: ProductType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ProductType $productType;

    #[ORM\Embedded(class: Money::class, columnPrefix: 'base_price_')]
    private Money $basePrice;

    #[ORM\Column(type: 'string', length: 20, enumType: ProductStatus::class)]
    private ProductStatus $status;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $taxRate = '23.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3, nullable: true)]
    private ?string $weightKg = null;

    /** @var Collection<int, ProductInstance> */
    #[ORM\OneToMany(targetEntity: ProductInstance::class, mappedBy: 'product', cascade: ['persist'])]
    private Collection $instances;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $sku, string $name, ProductType $productType, Money $basePrice)
    {
        $this->id = Uuid::v7();
        $this->sku = $sku;
        $this->name = $name;
        $this->productType = $productType;
        $this->basePrice = $basePrice;
        $this->status = ProductStatus::Draft;
        $this->instances = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProductType(): ProductType
    {
        return $this->productType;
    }

    public function getBasePrice(): Money
    {
        return $this->basePrice;
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function getTaxRate(): float
    {
        return (float) $this->taxRate;
    }

    public function setTaxRate(float $rate): void
    {
        $this->taxRate = (string) $rate;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setWeightKg(?float $weight): void
    {
        $this->weightKg = $weight !== null ? (string) $weight : null;
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    public function publish(): void
    {
        if ($this->status !== ProductStatus::Draft) {
            throw new \DomainException('Can only publish draft products');
        }
        $this->status = ProductStatus::Active;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function discontinue(): void
    {
        if ($this->status !== ProductStatus::Active) {
            throw new \DomainException('Can only discontinue active products');
        }
        $this->status = ProductStatus::Discontinued;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
