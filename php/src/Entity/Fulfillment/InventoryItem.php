<?php

namespace App\Entity\Fulfillment;

use App\Entity\Product\Product;
use App\Enum\TransactionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_item')]
#[ORM\UniqueConstraint(columns: ['product_id', 'location_id'])]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Location $location;

    #[ORM\Column(type: 'integer')]
    private int $quantityOnHand = 0;

    #[ORM\Column(type: 'integer')]
    private int $quantityReserved = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reorderLevel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxLevel = null;

    /** @var Collection<int, InventoryTransaction> */
    #[ORM\OneToMany(targetEntity: InventoryTransaction::class, mappedBy: 'inventoryItem', cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $transactions;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Product $product, Location $location)
    {
        $this->id = Uuid::v7();
        $this->product = $product;
        $this->location = $location;
        $this->transactions = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getQuantityOnHand(): int
    {
        return $this->quantityOnHand;
    }

    public function getQuantityReserved(): int
    {
        return $this->quantityReserved;
    }

    public function getQuantityAvailable(): int
    {
        return $this->quantityOnHand - $this->quantityReserved;
    }

    public function needsReorder(): bool
    {
        return $this->reorderLevel !== null && $this->quantityOnHand <= $this->reorderLevel;
    }

    public function setReorderLevel(?int $level): void
    {
        $this->reorderLevel = $level;
    }

    public function receive(int $quantity, ?string $reference = null, ?string $notes = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Receive quantity must be positive');
        }
        $this->quantityOnHand += $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Receipt, $quantity, $reference, $notes);
    }

    public function issue(int $quantity, ?string $reference = null, ?string $notes = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Issue quantity must be positive');
        }
        if ($quantity > $this->quantityOnHand) {
            throw new \DomainException("Cannot issue {$quantity}, only {$this->quantityOnHand} on hand");
        }
        $this->quantityOnHand -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Issue, -$quantity, $reference, $notes);
    }

    public function reserve(int $quantity, ?string $reference = null, ?string $notes = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Reserve quantity must be positive');
        }
        if ($quantity > $this->getQuantityAvailable()) {
            throw new \DomainException("Cannot reserve {$quantity}, only {$this->getQuantityAvailable()} available");
        }
        $this->quantityReserved += $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Reserve, $quantity, $reference, $notes);
    }

    public function release(int $quantity, ?string $reference = null, ?string $notes = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Release quantity must be positive');
        }
        if ($quantity > $this->quantityReserved) {
            throw new \DomainException("Cannot release {$quantity}, only {$this->quantityReserved} reserved");
        }
        $this->quantityReserved -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Release, -$quantity, $reference, $notes);
    }

    public function shipReserved(int $quantity, ?string $reference = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Ship quantity must be positive');
        }
        if ($quantity > $this->quantityReserved) {
            throw new \DomainException("Cannot ship {$quantity}, only {$this->quantityReserved} reserved");
        }
        $this->quantityOnHand -= $quantity;
        $this->quantityReserved -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Issue, -$quantity, $reference, 'Shipped from reserved stock');
    }

    public function adjust(int $quantity, ?string $reference = null, ?string $notes = null): InventoryTransaction
    {
        $newOnHand = $this->quantityOnHand + $quantity;
        if ($newOnHand < 0) {
            throw new \DomainException('Adjustment would result in negative stock');
        }
        $this->quantityOnHand = $newOnHand;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::Adjustment, $quantity, $reference, $notes);
    }

    public function transferOut(int $quantity, string $destinationCode, ?string $reference = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Transfer quantity must be positive');
        }
        if ($quantity > $this->getQuantityAvailable()) {
            throw new \DomainException('Insufficient available stock for transfer');
        }
        $this->quantityOnHand -= $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::TransferOut, -$quantity, $reference, "Transfer to {$destinationCode}");
    }

    public function transferIn(int $quantity, string $sourceCode, ?string $reference = null): InventoryTransaction
    {
        if ($quantity <= 0) {
            throw new \DomainException('Transfer quantity must be positive');
        }
        $this->quantityOnHand += $quantity;
        $this->updatedAt = new \DateTimeImmutable();

        return $this->createTransaction(TransactionType::TransferIn, $quantity, $reference, "Transfer from {$sourceCode}");
    }

    private function createTransaction(TransactionType $type, int $quantity, ?string $reference, ?string $notes): InventoryTransaction
    {
        $tx = new InventoryTransaction($this, $type, $quantity, $this->quantityOnHand, $reference, $notes);
        $this->transactions->add($tx);
        return $tx;
    }

    /** @return Collection<int, InventoryTransaction> */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }
}
