package com.archetypes.entity.fulfillment;

import com.archetypes.enums.ShipmentStatus;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.UUID;

@Entity
@Table(name = "shipment_event")
public class ShipmentEvent {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "shipment_id", nullable = false)
    private Shipment shipment;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private ShipmentStatus status;

    @Column(length = 255, nullable = false)
    private String description;

    @Column(length = 100)
    private String location;

    @Column(length = 50)
    private String carrierEventCode;

    @Column(nullable = false)
    private Instant occurredAt;

    protected ShipmentEvent() {
    }

    public ShipmentEvent(Shipment shipment, ShipmentStatus status, String description, String location) {
        this.id = UUID.randomUUID();
        this.shipment = shipment;
        this.status = status;
        this.description = description;
        this.location = location;
        this.occurredAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public ShipmentStatus getStatus() {
        return status;
    }

    public String getDescription() {
        return description;
    }

    public String getLocation() {
        return location;
    }

    public Instant getOccurredAt() {
        return occurredAt;
    }

    public void setCarrierEventCode(String code) {
        this.carrierEventCode = code;
    }
}
