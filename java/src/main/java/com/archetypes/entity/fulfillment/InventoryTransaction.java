package com.archetypes.entity.fulfillment;

import com.archetypes.enums.TransactionType;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.UUID;

@Entity
@Table(name = "inventory_transaction")
public class InventoryTransaction {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "inventory_item_id", nullable = false)
    private InventoryItem inventoryItem;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private TransactionType type;

    @Column(nullable = false)
    private int quantity;

    @Column(nullable = false)
    private int balanceAfter;

    @Column(length = 50)
    private String reference;

    @Column(columnDefinition = "text")
    private String notes;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "related_transaction_id")
    private InventoryTransaction relatedTransaction;

    @Column(nullable = false)
    private Instant createdAt;

    protected InventoryTransaction() {
    }

    public InventoryTransaction(InventoryItem inventoryItem, TransactionType type, int quantity,
                                int balanceAfter, String reference, String notes) {
        this.id = UUID.randomUUID();
        this.inventoryItem = inventoryItem;
        this.type = type;
        this.quantity = quantity;
        this.balanceAfter = balanceAfter;
        this.reference = reference;
        this.notes = notes;
        this.createdAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public TransactionType getType() {
        return type;
    }

    public int getQuantity() {
        return quantity;
    }

    public int getBalanceAfter() {
        return balanceAfter;
    }

    public String getReference() {
        return reference;
    }

    public String getNotes() {
        return notes;
    }

    public InventoryTransaction getRelatedTransaction() {
        return relatedTransaction;
    }

    public void setRelatedTransaction(InventoryTransaction relatedTransaction) {
        this.relatedTransaction = relatedTransaction;
    }

    public Instant getCreatedAt() {
        return createdAt;
    }
}
