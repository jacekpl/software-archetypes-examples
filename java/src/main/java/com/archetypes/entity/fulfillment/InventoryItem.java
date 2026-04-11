package com.archetypes.entity.fulfillment;

import com.archetypes.entity.product.Product;
import com.archetypes.enums.TransactionType;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "inventory_item", uniqueConstraints = {
        @UniqueConstraint(columnNames = {"product_id", "location_id"})
})
public class InventoryItem {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_id", nullable = false)
    private Product product;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "location_id", nullable = false)
    private Location location;

    @Column(nullable = false)
    private int quantityOnHand = 0;

    @Column(nullable = false)
    private int quantityReserved = 0;

    private Integer reorderLevel;

    private Integer maxLevel;

    @OneToMany(mappedBy = "inventoryItem", cascade = CascadeType.PERSIST)
    @OrderBy("createdAt ASC")
    private List<InventoryTransaction> transactions;

    @Column(nullable = false)
    private Instant updatedAt;

    protected InventoryItem() {
    }

    public InventoryItem(Product product, Location location) {
        this.id = UUID.randomUUID();
        this.product = product;
        this.location = location;
        this.transactions = new ArrayList<>();
        this.updatedAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public Product getProduct() {
        return product;
    }

    public Location getLocation() {
        return location;
    }

    public int getQuantityOnHand() {
        return quantityOnHand;
    }

    public int getQuantityReserved() {
        return quantityReserved;
    }

    public int getQuantityAvailable() {
        return quantityOnHand - quantityReserved;
    }

    public boolean needsReorder() {
        return reorderLevel != null && quantityOnHand <= reorderLevel;
    }

    public void setReorderLevel(Integer level) {
        this.reorderLevel = level;
    }

    public InventoryTransaction receive(int quantity, String reference, String notes) {
        if (quantity <= 0) {
            throw new IllegalStateException("Receive quantity must be positive");
        }
        this.quantityOnHand += quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Receipt, quantity, reference, notes);
    }

    public InventoryTransaction issue(int quantity, String reference, String notes) {
        if (quantity <= 0) {
            throw new IllegalStateException("Issue quantity must be positive");
        }
        if (quantity > quantityOnHand) {
            throw new IllegalStateException("Cannot issue " + quantity + ", only " + quantityOnHand + " on hand");
        }
        this.quantityOnHand -= quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Issue, -quantity, reference, notes);
    }

    public InventoryTransaction reserve(int quantity, String reference, String notes) {
        if (quantity <= 0) {
            throw new IllegalStateException("Reserve quantity must be positive");
        }
        if (quantity > getQuantityAvailable()) {
            throw new IllegalStateException("Cannot reserve " + quantity + ", only " + getQuantityAvailable() + " available");
        }
        this.quantityReserved += quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Reserve, quantity, reference, notes);
    }

    public InventoryTransaction release(int quantity, String reference, String notes) {
        if (quantity <= 0) {
            throw new IllegalStateException("Release quantity must be positive");
        }
        if (quantity > quantityReserved) {
            throw new IllegalStateException("Cannot release " + quantity + ", only " + quantityReserved + " reserved");
        }
        this.quantityReserved -= quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Release, -quantity, reference, notes);
    }

    public InventoryTransaction shipReserved(int quantity, String reference) {
        if (quantity <= 0) {
            throw new IllegalStateException("Ship quantity must be positive");
        }
        if (quantity > quantityReserved) {
            throw new IllegalStateException("Cannot ship " + quantity + ", only " + quantityReserved + " reserved");
        }
        this.quantityOnHand -= quantity;
        this.quantityReserved -= quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Issue, -quantity, reference, "Shipped from reserved stock");
    }

    public InventoryTransaction adjust(int quantity, String reference, String notes) {
        int newOnHand = quantityOnHand + quantity;
        if (newOnHand < 0) {
            throw new IllegalStateException("Adjustment would result in negative stock");
        }
        this.quantityOnHand = newOnHand;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.Adjustment, quantity, reference, notes);
    }

    public InventoryTransaction transferOut(int quantity, String destinationCode, String reference) {
        if (quantity <= 0) {
            throw new IllegalStateException("Transfer quantity must be positive");
        }
        if (quantity > getQuantityAvailable()) {
            throw new IllegalStateException("Insufficient available stock for transfer");
        }
        this.quantityOnHand -= quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.TransferOut, -quantity, reference, "Transfer to " + destinationCode);
    }

    public InventoryTransaction transferIn(int quantity, String sourceCode, String reference) {
        if (quantity <= 0) {
            throw new IllegalStateException("Transfer quantity must be positive");
        }
        this.quantityOnHand += quantity;
        this.updatedAt = Instant.now();
        return createTransaction(TransactionType.TransferIn, quantity, reference, "Transfer from " + sourceCode);
    }

    private InventoryTransaction createTransaction(TransactionType type, int quantity, String reference, String notes) {
        InventoryTransaction tx = new InventoryTransaction(this, type, quantity, quantityOnHand, reference, notes);
        this.transactions.add(tx);
        return tx;
    }

    public List<InventoryTransaction> getTransactions() {
        return transactions;
    }
}
