package com.archetypes.entity.commerce;

import com.archetypes.embeddable.Money;
import com.archetypes.entity.party.Customer;
import com.archetypes.entity.product.Product;
import com.archetypes.enums.OrderStatus;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.UUID;

@Entity
@Table(name = "orders")
public class Order {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 30, unique = true, nullable = false)
    private String orderNumber;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "customer_id", nullable = false)
    private Customer customer;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private OrderStatus status;

    @Column(nullable = false)
    private Instant orderDate;

    private Instant confirmedAt;

    @Column(length = 3, nullable = false)
    private String currency;

    @Column(columnDefinition = "text", nullable = false)
    private String billingAddress;

    @Column(columnDefinition = "text")
    private String shippingAddress;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "subtotal_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "subtotal_currency", length = 3))
    })
    private Money subtotal;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "discount_total_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "discount_total_currency", length = 3))
    })
    private Money discountTotal;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "tax_total_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "tax_total_currency", length = 3))
    })
    private Money taxTotal;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "shipping_cost_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "shipping_cost_currency", length = 3))
    })
    private Money shippingCost;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "grand_total_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "grand_total_currency", length = 3))
    })
    private Money grandTotal;

    @Column(columnDefinition = "text")
    private String notes;

    private Instant cancelledAt;

    @Column(columnDefinition = "text")
    private String cancellationReason;

    @OneToMany(mappedBy = "order", cascade = {CascadeType.PERSIST, CascadeType.REMOVE}, orphanRemoval = true)
    @OrderBy("lineNumber ASC")
    private List<OrderLine> lines;

    @Column(nullable = false)
    private Instant createdAt;

    protected Order() {
    }

    public Order(String orderNumber, Customer customer, String currency, Map<String, String> billingAddress) {
        this.id = UUID.randomUUID();
        this.orderNumber = orderNumber;
        this.customer = customer;
        this.currency = currency;
        this.billingAddress = mapToJson(billingAddress);
        this.status = OrderStatus.Draft;
        this.orderDate = Instant.now();
        this.subtotal = Money.zero(currency);
        this.discountTotal = Money.zero(currency);
        this.taxTotal = Money.zero(currency);
        this.shippingCost = Money.zero(currency);
        this.grandTotal = Money.zero(currency);
        this.lines = new ArrayList<>();
        this.createdAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public String getOrderNumber() {
        return orderNumber;
    }

    public Customer getCustomer() {
        return customer;
    }

    public OrderStatus getStatus() {
        return status;
    }

    public String getCurrency() {
        return currency;
    }

    public Money getSubtotal() {
        return subtotal;
    }

    public Money getTaxTotal() {
        return taxTotal;
    }

    public Money getGrandTotal() {
        return grandTotal;
    }

    public List<OrderLine> getLines() {
        return lines;
    }

    public String getShippingAddress() {
        return shippingAddress;
    }

    public void setShippingAddress(Map<String, String> address) {
        this.shippingAddress = mapToJson(address);
    }

    public void setNotes(String notes) {
        this.notes = notes;
    }

    public OrderLine addLine(Product product, int quantity, Money unitPrice) {
        if (status != OrderStatus.Draft) {
            throw new IllegalStateException("Cannot modify confirmed order");
        }
        if (quantity <= 0) {
            throw new IllegalStateException("Quantity must be positive");
        }

        OrderLine line = new OrderLine(this, lines.size() + 1, product, quantity, unitPrice, product.getTaxRate());
        lines.add(line);
        recalculateTotals();
        return line;
    }

    public void recalculateTotals() {
        int subtotalCents = 0;
        int taxCents = 0;
        for (OrderLine line : lines) {
            subtotalCents += line.getLineTotal().getCents();
            taxCents += line.getTaxAmount().getCents();
        }
        this.subtotal = new Money(subtotalCents, currency);
        this.taxTotal = new Money(taxCents, currency);
        this.grandTotal = new Money(
                subtotalCents + taxCents + shippingCost.getCents() - discountTotal.getCents(),
                currency
        );
    }

    public void confirm() {
        if (status != OrderStatus.Draft) {
            throw new IllegalStateException("Can only confirm draft orders");
        }
        if (lines.isEmpty()) {
            throw new IllegalStateException("Order must have at least one line");
        }
        this.status = OrderStatus.Confirmed;
        this.confirmedAt = Instant.now();
    }

    public void markPaid(Payment payment) {
        if (status != OrderStatus.Confirmed) {
            throw new IllegalStateException("Order must be confirmed before payment");
        }
        this.status = OrderStatus.Paid;
    }

    public void startProcessing() {
        if (status != OrderStatus.Paid) {
            throw new IllegalStateException("Order must be paid before processing");
        }
        this.status = OrderStatus.Processing;
    }

    public void markShipped() {
        if (status != OrderStatus.Processing) {
            throw new IllegalStateException("Order must be processing to ship");
        }
        this.status = OrderStatus.Shipped;
    }

    public void markDelivered() {
        if (status != OrderStatus.Shipped) {
            throw new IllegalStateException("Order must be shipped to deliver");
        }
        this.status = OrderStatus.Delivered;
    }

    public void complete() {
        if (status != OrderStatus.Delivered) {
            throw new IllegalStateException("Order must be delivered to complete");
        }
        this.status = OrderStatus.Completed;
    }

    public void cancel(String reason) {
        if (status != OrderStatus.Draft && status != OrderStatus.Confirmed) {
            throw new IllegalStateException("Cannot cancel order in status " + status.name());
        }
        this.status = OrderStatus.Cancelled;
        this.cancelledAt = Instant.now();
        this.cancellationReason = reason;
    }

    private static String mapToJson(Map<String, String> map) {
        if (map == null) return null;
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
