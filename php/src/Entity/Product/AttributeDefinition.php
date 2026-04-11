<?php

namespace App\Entity\Product;

use App\Enum\DataType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'attribute_definition')]
#[ORM\UniqueConstraint(columns: ['product_type_id', 'code'])]
class AttributeDefinition
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ProductType::class, inversedBy: 'attributeDefinitions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductType $productType;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, enumType: DataType::class)]
    private DataType $dataType;

    #[ORM\Column(type: 'boolean')]
    private bool $required = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $options = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $minValue = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 4, nullable: true)]
    private ?string $maxValue = null;

    public function __construct(string $code, string $name, DataType $dataType, bool $required = false)
    {
        $this->id = Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->dataType = $dataType;
        $this->required = $required;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataType(): DataType
    {
        return $this->dataType;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setProductType(ProductType $productType): void
    {
        $this->productType = $productType;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function setRange(?float $min, ?float $max): void
    {
        $this->minValue = $min !== null ? (string) $min : null;
        $this->maxValue = $max !== null ? (string) $max : null;
    }

    public function setOptions(array $options): void
    {
        $this->options = json_encode($options);
    }
}
