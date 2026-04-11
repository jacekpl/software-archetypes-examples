package com.archetypes.entity.product;

import com.archetypes.enums.DataType;
import jakarta.persistence.*;
import java.math.BigDecimal;
import java.util.UUID;

@Entity
@Table(name = "attribute_definition", uniqueConstraints = {
        @UniqueConstraint(columnNames = {"product_type_id", "code"})
})
public class AttributeDefinition {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_type_id", nullable = false)
    private ProductType productType;

    @Column(length = 50, nullable = false)
    private String code;

    @Column(length = 100, nullable = false)
    private String name;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private DataType dataType;

    @Column(nullable = false)
    private boolean required = false;

    @Column(columnDefinition = "text")
    private String options;

    @Column(length = 20)
    private String unit;

    @Column(precision = 15, scale = 4)
    private BigDecimal minValue;

    @Column(precision = 15, scale = 4)
    private BigDecimal maxValue;

    protected AttributeDefinition() {
    }

    public AttributeDefinition(String code, String name, DataType dataType, boolean required) {
        this.id = UUID.randomUUID();
        this.code = code;
        this.name = name;
        this.dataType = dataType;
        this.required = required;
    }

    public UUID getId() {
        return id;
    }

    public String getCode() {
        return code;
    }

    public String getName() {
        return name;
    }

    public DataType getDataType() {
        return dataType;
    }

    public boolean isRequired() {
        return required;
    }

    public void setProductType(ProductType productType) {
        this.productType = productType;
    }

    public void setUnit(String unit) {
        this.unit = unit;
    }

    public void setRange(Double min, Double max) {
        this.minValue = min != null ? BigDecimal.valueOf(min) : null;
        this.maxValue = max != null ? BigDecimal.valueOf(max) : null;
    }

    public void setOptions(String options) {
        this.options = options;
    }
}
