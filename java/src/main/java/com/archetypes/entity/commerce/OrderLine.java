package com.archetypes.entity.commerce;

import com.archetypes.embeddable.Money;
import com.archetypes.entity.product.Product;
import jakarta.persistence.*;
import java.math.BigDecimal;
import java.util.UUID;

@Entity
@Table(name = "order_line", uniqueConstraints = {
        @UniqueConstraint(columnNames = {"order_id", "line_number"})
})
public class OrderLine {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "order_id", nullable = false)
    private Order order;

    @Column(name = "line_number", nullable = false)
    private int lineNumber;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "product_id")
    private Product product;

    @Column(length = 255, nullable = false)
    private String productName;

    @Column(length = 50, nullable = false)
    private String productSku;

    @Column(nullable = false)
    private int quantity;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "unit_price_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "unit_price_currency", length = 3))
    })
    private Money unitPrice;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "discount_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "discount_currency", length = 3))
    })
    private Money discount;

    @Column(precision = 5, scale = 2, nullable = false)
    private BigDecimal taxRate;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "tax_amount_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "tax_amount_currency", length = 3))
    })
    private Money taxAmount;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "line_total_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "line_total_currency", length = 3))
    })
    private Money lineTotal;

    protected OrderLine() {
    }

    public OrderLine(Order order, int lineNumber, Product product, int quantity, Money unitPrice, double taxRate) {
        this.id = UUID.randomUUID();
        this.order = order;
        this.lineNumber = lineNumber;
        this.product = product;
        this.productName = product.getName();
        this.productSku = product.getSku();
        this.quantity = quantity;
        this.unitPrice = unitPrice;
        this.taxRate = BigDecimal.valueOf(taxRate);
        this.discount = Money.zero(unitPrice.getCurrency());
        recalculate();
    }

    public UUID getId() {
        return id;
    }

    public Order getOrder() {
        return order;
    }

    public int getLineNumber() {
        return lineNumber;
    }

    public Product getProduct() {
        return product;
    }

    public String getProductName() {
        return productName;
    }

    public String getProductSku() {
        return productSku;
    }

    public int getQuantity() {
        return quantity;
    }

    public Money getUnitPrice() {
        return unitPrice;
    }

    public Money getLineTotal() {
        return lineTotal;
    }

    public Money getTaxAmount() {
        return taxAmount;
    }

    public void recalculate() {
        int lineCents = (unitPrice.getCents() * quantity) - discount.getCents();
        int taxCents = (int) Math.round(lineCents * taxRate.doubleValue() / 100.0);
        String cur = unitPrice.getCurrency();

        this.lineTotal = new Money(lineCents, cur);
        this.taxAmount = new Money(taxCents, cur);
    }
}
