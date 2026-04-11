package com.archetypes.entity.fulfillment;

import com.archetypes.embeddable.Money;
import com.archetypes.entity.commerce.Order;
import com.archetypes.entity.commerce.OrderLine;
import com.archetypes.entity.product.Product;
import com.archetypes.enums.ShipmentStatus;
import jakarta.persistence.*;
import java.time.Instant;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.UUID;

@Entity
@Table(name = "shipment")
public class Shipment {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 50, unique = true, nullable = false)
    private String shipmentNumber;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "order_id")
    private Order order;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "carrier_id")
    private Carrier carrier;

    @Column(length = 100)
    private String trackingNumber;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private ShipmentStatus status;

    @Column(columnDefinition = "text", nullable = false)
    private String shipFromAddress;

    @Column(columnDefinition = "text", nullable = false)
    private String shipToAddress;

    private Integer weightGrams;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "shipping_cost_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "shipping_cost_currency", length = 3))
    })
    private Money shippingCost;

    @Column(columnDefinition = "text")
    private String proofOfDelivery;

    @OneToMany(mappedBy = "shipment", cascade = {CascadeType.PERSIST, CascadeType.REMOVE})
    private List<ShipmentItem> items;

    @OneToMany(mappedBy = "shipment", cascade = {CascadeType.PERSIST, CascadeType.REMOVE})
    @OrderBy("occurredAt ASC")
    private List<ShipmentEvent> events;

    @Column(nullable = false)
    private Instant createdAt;

    private Instant shippedAt;

    private Instant deliveredAt;

    protected Shipment() {
    }

    public Shipment(String shipmentNumber, Map<String, String> shipFromAddress, Map<String, String> shipToAddress) {
        this.id = UUID.randomUUID();
        this.shipmentNumber = shipmentNumber;
        this.shipFromAddress = mapToJson(shipFromAddress);
        this.shipToAddress = mapToJson(shipToAddress);
        this.status = ShipmentStatus.Created;
        this.items = new ArrayList<>();
        this.shippingCost = Money.zero("PLN");
        this.events = new ArrayList<>();
        this.createdAt = Instant.now();
        addEvent(ShipmentStatus.Created, "Shipment created", null);
    }

    public static Shipment fromOrder(Order order, String shipmentNumber, Map<String, String> shipFromAddress) {
        String shipTo = order.getShippingAddress() != null ? order.getShippingAddress() : "{}";
        Shipment shipment = new Shipment();
        shipment.id = UUID.randomUUID();
        shipment.shipmentNumber = shipmentNumber;
        shipment.shipFromAddress = mapToJson(shipFromAddress);
        shipment.shipToAddress = shipTo;
        shipment.order = order;
        shipment.status = ShipmentStatus.Created;
        shipment.items = new ArrayList<>();
        shipment.shippingCost = Money.zero("PLN");
        shipment.events = new ArrayList<>();
        shipment.createdAt = Instant.now();
        shipment.addEvent(ShipmentStatus.Created, "Shipment created", null);
        return shipment;
    }

    public UUID getId() {
        return id;
    }

    public String getShipmentNumber() {
        return shipmentNumber;
    }

    public Order getOrder() {
        return order;
    }

    public Carrier getCarrier() {
        return carrier;
    }

    public String getTrackingNumber() {
        return trackingNumber;
    }

    public ShipmentStatus getStatus() {
        return status;
    }

    public List<ShipmentItem> getItems() {
        return items;
    }

    public List<ShipmentEvent> getEvents() {
        return events;
    }

    public ShipmentItem addItem(Product product, int quantity) {
        ShipmentItem item = new ShipmentItem(this, product, quantity);
        items.add(item);
        return item;
    }

    public ShipmentItem addItemFromOrderLine(OrderLine orderLine, int quantity) {
        ShipmentItem item = addItem(orderLine.getProduct(), quantity);
        item.setOrderLine(orderLine);
        return item;
    }

    public void assignCarrier(Carrier carrier, String trackingNumber) {
        this.carrier = carrier;
        this.trackingNumber = trackingNumber;
    }

    // === Status transitions ===

    public void startPicking() {
        requireStatus(ShipmentStatus.Created);
        updateStatus(ShipmentStatus.Picking, "Picking started");
    }

    public void markPacked() {
        requireStatus(ShipmentStatus.Picking);
        updateStatus(ShipmentStatus.Packed, "Packed");
    }

    public void markLabelPrinted() {
        requireStatus(ShipmentStatus.Packed);
        if (carrier == null) {
            throw new IllegalStateException("Carrier must be assigned before printing label");
        }
        updateStatus(ShipmentStatus.LabelPrinted, "Label printed");
    }

    public void handOverToCarrier() {
        requireStatus(ShipmentStatus.LabelPrinted);
        this.shippedAt = Instant.now();
        updateStatus(ShipmentStatus.HandedOver, "Handed to " + carrier.getName());
    }

    public void markInTransit(String location) {
        if (status != ShipmentStatus.HandedOver && status != ShipmentStatus.InTransit) {
            throw new IllegalStateException("Shipment not yet handed to carrier");
        }
        updateStatus(ShipmentStatus.InTransit, "In transit", location);
    }

    public void markOutForDelivery() {
        requireStatus(ShipmentStatus.InTransit);
        updateStatus(ShipmentStatus.OutForDelivery, "Out for delivery");
    }

    public void markDelivered(Map<String, String> proof) {
        requireStatus(ShipmentStatus.OutForDelivery);
        this.deliveredAt = Instant.now();
        this.proofOfDelivery = proof != null ? mapToJson(proof) : null;
        updateStatus(ShipmentStatus.Delivered, "Delivered");
    }

    public void markDeliveryFailed(String reason) {
        requireStatus(ShipmentStatus.OutForDelivery);
        updateStatus(ShipmentStatus.DeliveryFailed, "Failed: " + reason);
    }

    public void cancel(String reason) {
        if (status.isFinal()) {
            throw new IllegalStateException("Cannot cancel shipment in final status");
        }
        updateStatus(ShipmentStatus.Cancelled, "Cancelled: " + reason);
    }

    public String getTrackingUrl() {
        if (carrier == null || trackingNumber == null) {
            return null;
        }
        return carrier.getTrackingUrl(trackingNumber);
    }

    public Long getTransitDays() {
        if (shippedAt == null || deliveredAt == null) {
            return null;
        }
        return ChronoUnit.DAYS.between(shippedAt, deliveredAt);
    }

    // === Helpers ===

    private void requireStatus(ShipmentStatus expected) {
        if (status != expected) {
            throw new IllegalStateException("Expected status " + expected.name() + ", got " + status.name());
        }
    }

    private void updateStatus(ShipmentStatus newStatus, String description) {
        updateStatus(newStatus, description, null);
    }

    private void updateStatus(ShipmentStatus newStatus, String description, String location) {
        this.status = newStatus;
        addEvent(newStatus, description, location);
    }

    private ShipmentEvent addEvent(ShipmentStatus status, String description, String location) {
        ShipmentEvent event = new ShipmentEvent(this, status, description, location);
        events.add(event);
        return event;
    }

    private static String mapToJson(Map<String, String> map) {
        if (map == null) return "{}";
        StringBuilder sb = new StringBuilder("{");
        boolean first = true;
        for (Map.Entry<String, String> entry : map.entrySet()) {
            if (!first) sb.append(",");
            sb.append("\"").append(entry.getKey()).append("\":\"").append(entry.getValue()).append("\"");
            first = false;
        }
        sb.append("}");
        return sb.toString();
    }
}
