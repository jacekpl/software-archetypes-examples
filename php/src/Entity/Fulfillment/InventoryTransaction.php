<?php

namespace App\Entity\Fulfillment;

use App\Enum\TransactionType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inventory_transaction')]
class InventoryTransaction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private InventoryItem $inventoryItem;

    #[ORM\Column(type: 'string', length: 20, enumType: TransactionType::class)]
    private TransactionType $type;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(type: 'integer')]
    private int $balanceAfter;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $relatedTransaction = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        InventoryItem $inventoryItem,
        TransactionType $type,
        int $quantity,
        int $balanceAfter,
        ?string $reference = null,
        ?string $notes = null,
    ) {
        $this->id = Uuid::v7();
        $this->inventoryItem = $inventoryItem;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->balanceAfter = $balanceAfter;
        $this->reference = $reference;
        $this->notes = $notes;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getBalanceAfter(): int
    {
        return $this->balanceAfter;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getRelatedTransaction(): ?self
    {
        return $this->relatedTransaction;
    }

    public function setRelatedTransaction(?self $relatedTransaction): void
    {
        $this->relatedTransaction = $relatedTransaction;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
