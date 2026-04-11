<?php

namespace App\Entity\Fulfillment;

use App\Enum\LocationType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'location')]
class Location
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 30, unique: true)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, enumType: LocationType::class)]
    private LocationType $type;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\Column(type: 'boolean')]
    private bool $isStockable = true;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $code, string $name, LocationType $type, ?self $parent = null)
    {
        $this->id = Uuid::v7();
        $this->code = $code;
        $this->name = $name;
        $this->type = $type;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
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

    public function getType(): LocationType
    {
        return $this->type;
    }

    public function getPath(): string
    {
        if ($this->parent === null) {
            return $this->name;
        }
        return $this->parent->getPath() . ' / ' . $this->name;
    }

    public function canStoreInventory(): bool
    {
        return $this->isStockable && $this->isActive;
    }
}
