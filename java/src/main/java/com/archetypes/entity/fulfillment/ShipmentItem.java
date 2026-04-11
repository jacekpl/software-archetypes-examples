package com.archetypes.entity.fulfillment;

import com.archetypes.entity.commerce.OrderLine;
import com.archetypes.entity.product.Product;
import jakarta.persistence.*;
import java.util.UUID;

@Entity
@Table(name = "shipment_item")
public class ShipmentItem {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "shipment_id", nullable = false)
    private Shipment shipment;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "order_line_id")
    private OrderLine orderLine;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_id", nullable = false)
    private Product product;

    @Column(nullable = false)
    private int quantity;

    @Column(length = 100)
    private String serialNumber;

    @Column(length = 50)
    private String batchNumber;

    protected ShipmentItem() {
    }

    public ShipmentItem(Shipment shipment, Product product, int quantity) {
        this.id = UUID.randomUUID();
        this.shipment = shipment;
        this.product = product;
        this.quantity = quantity;
    }

    public UUID getId() {
        return id;
    }

    public Product getProduct() {
        return product;
    }

    public int getQuantity() {
        return quantity;
    }

    public void setOrderLine(OrderLine orderLine) {
        this.orderLine = orderLine;
    }

    public void setSerialNumber(String serialNumber) {
        this.serialNumber = serialNumber;
    }

    public void setBatchNumber(String batchNumber) {
        this.batchNumber = batchNumber;
    }
}
