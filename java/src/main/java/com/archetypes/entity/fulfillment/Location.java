package com.archetypes.entity.fulfillment;

import com.archetypes.enums.LocationType;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "location")
public class Location {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 30, unique = true, nullable = false)
    private String code;

    @Column(length = 100, nullable = false)
    private String name;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private LocationType type;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "parent_id")
    private Location parent;

    @OneToMany(mappedBy = "parent")
    private List<Location> children;

    @Column(nullable = false)
    private boolean isStockable = true;

    @Column(nullable = false)
    private boolean isActive = true;

    @Column(nullable = false)
    private Instant createdAt;

    protected Location() {
    }

    public Location(String code, String name, LocationType type) {
        this(code, name, type, null);
    }

    public Location(String code, String name, LocationType type, Location parent) {
        this.id = UUID.randomUUID();
        this.code = code;
        this.name = name;
        this.type = type;
        this.parent = parent;
        this.children = new ArrayList<>();
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

    public LocationType getType() {
        return type;
    }

    public String getPath() {
        if (parent == null) {
            return name;
        }
        return parent.getPath() + " / " + name;
    }

    public boolean canStoreInventory() {
        return isStockable && isActive;
    }
}
