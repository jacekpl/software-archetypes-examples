<?php

namespace App\Entity\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'product_type')]
class ProductType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    /** @var Collection<int, AttributeDefinition> */
    #[ORM\OneToMany(targetEntity: AttributeDefinition::class, mappedBy: 'productType', cascade: ['persist', 'remove'])]
    private Collection $attributeDefinitions;

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $code, string $name, ?self $parent = null)
    {
        $this->id = Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
        $this->attributeDefinitions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /** @return string[] */
    public function getPath(): array
    {
        if ($this->parent === null) {
            return [$this->name];
        }
        return array_merge($this->parent->getPath(), [$this->name]);
    }

    public function addAttributeDefinition(AttributeDefinition $definition): void
    {
        $definition->setProductType($this);
        $this->attributeDefinitions->add($definition);
    }

    /** @return Collection<int, AttributeDefinition> */
    public function getAttributeDefinitions(): Collection
    {
        return $this->attributeDefinitions;
    }

    /** @return AttributeDefinition[] */
    public function getAllAttributeDefinitions(): array
    {
        $inherited = $this->parent?->getAllAttributeDefinitions() ?? [];
        return array_merge($inherited, $this->attributeDefinitions->toArray());
    }
}
