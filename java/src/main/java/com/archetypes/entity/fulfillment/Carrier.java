package com.archetypes.entity.fulfillment;

import jakarta.persistence.*;
import java.util.UUID;

@Entity
@Table(name = "carrier")
public class Carrier {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 20, unique = true, nullable = false)
    private String code;

    @Column(length = 100, nullable = false)
    private String name;

    @Column(length = 255)
    private String trackingUrlTemplate;

    @Column(nullable = false)
    private boolean isActive = true;

    protected Carrier() {
    }

    public Carrier(String code, String name, String trackingUrlTemplate) {
        this.id = UUID.randomUUID();
        this.code = code;
        this.name = name;
        this.trackingUrlTemplate = trackingUrlTemplate;
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

    public String getTrackingUrl(String trackingNumber) {
        if (trackingUrlTemplate == null) {
            return null;
        }
        return trackingUrlTemplate.replace("{tracking}", trackingNumber);
    }
}
