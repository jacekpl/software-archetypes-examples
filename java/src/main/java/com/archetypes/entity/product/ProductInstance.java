package com.archetypes.entity.product;

import com.archetypes.enums.InstanceStatus;
import jakarta.persistence.*;
import java.time.Instant;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Table(name = "product_instance")
public class ProductInstance {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_id", nullable = false)
    private Product product;

    @Column(length = 100, unique = true)
    private String serialNumber;

    @Column(length = 50)
    private String batchNumber;

    private LocalDate manufacturedDate;

    private LocalDate expiryDate;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private InstanceStatus status;

    @Column(nullable = false)
    private Instant createdAt;

    protected ProductInstance() {
    }

    public ProductInstance(Product product) {
        this.id = UUID.randomUUID();
        this.product = product;
        this.status = InstanceStatus.Available;
        this.createdAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public Product getProduct() {
        return product;
    }

    public String getSerialNumber() {
        return serialNumber;
    }

    public void setSerialNumber(String serialNumber) {
        this.serialNumber = serialNumber;
    }

    public void setBatchNumber(String batchNumber) {
        this.batchNumber = batchNumber;
    }

    public void setManufacturedDate(LocalDate date) {
        this.manufacturedDate = date;
    }

    public void setExpiryDate(LocalDate date) {
        this.expiryDate = date;
    }

    public InstanceStatus getStatus() {
        return status;
    }

    public boolean isAvailable() {
        return status == InstanceStatus.Available;
    }

    public boolean isExpired() {
        return expiryDate != null && expiryDate.isBefore(LocalDate.now());
    }
}
