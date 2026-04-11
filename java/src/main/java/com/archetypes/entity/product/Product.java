package com.archetypes.entity.product;

import com.archetypes.embeddable.Money;
import com.archetypes.enums.ProductStatus;
import jakarta.persistence.*;
import java.math.BigDecimal;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "product")
public class Product {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 50, unique = true, nullable = false)
    private String sku;

    @Column(length = 255, nullable = false)
    private String name;

    @Column(columnDefinition = "text")
    private String description;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_type_id", nullable = false)
    private ProductType productType;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "base_price_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "base_price_currency", length = 3))
    })
    private Money basePrice;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private ProductStatus status;

    @Column(precision = 5, scale = 2, nullable = false)
    private BigDecimal taxRate = new BigDecimal("23.00");

    @Column(precision = 10, scale = 3)
    private BigDecimal weightKg;

    @OneToMany(mappedBy = "product", cascade = CascadeType.PERSIST)
    private List<ProductInstance> instances;

    @Column(nullable = false)
    private Instant createdAt;

    @Column(nullable = false)
    private Instant updatedAt;

    protected Product() {
    }

    public Product(String sku, String name, ProductType productType, Money basePrice) {
        this.id = UUID.randomUUID();
        this.sku = sku;
        this.name = name;
        this.productType = productType;
        this.basePrice = basePrice;
        this.status = ProductStatus.Draft;
        this.instances = new ArrayList<>();
        this.createdAt = Instant.now();
        this.updatedAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public String getSku() {
        return sku;
    }

    public String getName() {
        return name;
    }

    public ProductType getProductType() {
        return productType;
    }

    public Money getBasePrice() {
        return basePrice;
    }

    public ProductStatus getStatus() {
        return status;
    }

    public double getTaxRate() {
        return taxRate.doubleValue();
    }

    public void setTaxRate(double rate) {
        this.taxRate = BigDecimal.valueOf(rate);
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public void setWeightKg(Double weight) {
        this.weightKg = weight != null ? BigDecimal.valueOf(weight) : null;
    }

    public boolean isAvailable() {
        return status == ProductStatus.Active;
    }

    public void publish() {
        if (status != ProductStatus.Draft) {
            throw new IllegalStateException("Can only publish draft products");
        }
        this.status = ProductStatus.Active;
        this.updatedAt = Instant.now();
    }

    public void discontinue() {
        if (status != ProductStatus.Active) {
            throw new IllegalStateException("Can only discontinue active products");
        }
        this.status = ProductStatus.Discontinued;
        this.updatedAt = Instant.now();
    }
}
