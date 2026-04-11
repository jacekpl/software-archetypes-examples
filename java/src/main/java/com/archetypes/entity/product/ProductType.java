package com.archetypes.entity.product;

import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "product_type")
public class ProductType {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 50, unique = true, nullable = false)
    private String code;

    @Column(length = 100, nullable = false)
    private String name;

    @Column(columnDefinition = "text")
    private String description;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "parent_id")
    private ProductType parent;

    @OneToMany(mappedBy = "parent")
    private List<ProductType> children;

    @OneToMany(mappedBy = "productType", cascade = {CascadeType.PERSIST, CascadeType.REMOVE})
    private List<AttributeDefinition> attributeDefinitions;

    @Column(nullable = false)
    private boolean active = true;

    @Column(nullable = false)
    private Instant createdAt;

    protected ProductType() {
    }

    public ProductType(String code, String name) {
        this(code, name, null);
    }

    public ProductType(String code, String name, ProductType parent) {
        this.id = UUID.randomUUID();
        this.code = code;
        this.name = name;
        this.parent = parent;
        this.children = new ArrayList<>();
        this.attributeDefinitions = new ArrayList<>();
        this.createdAt = Instant.now();
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

    public ProductType getParent() {
        return parent;
    }

    public boolean isLeaf() {
        return children.isEmpty();
    }

    public boolean isRoot() {
        return parent == null;
    }

    public List<String> getPath() {
        if (parent == null) {
            List<String> path = new ArrayList<>();
            path.add(name);
            return path;
        }
        List<String> path = parent.getPath();
        path.add(name);
        return path;
    }

    public void addAttributeDefinition(AttributeDefinition definition) {
        definition.setProductType(this);
        this.attributeDefinitions.add(definition);
    }

    public List<AttributeDefinition> getAttributeDefinitions() {
        return attributeDefinitions;
    }

    public List<AttributeDefinition> getAllAttributeDefinitions() {
        List<AttributeDefinition> inherited = parent != null ? parent.getAllAttributeDefinitions() : new ArrayList<>();
        inherited.addAll(attributeDefinitions);
        return inherited;
    }
}
