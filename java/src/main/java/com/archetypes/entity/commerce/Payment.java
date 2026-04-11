package com.archetypes.entity.commerce;

import com.archetypes.embeddable.Money;
import com.archetypes.entity.party.Customer;
import com.archetypes.enums.PaymentMethod;
import com.archetypes.enums.PaymentStatus;
import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "payment")
public class Payment {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @Column(length = 50, unique = true, nullable = false)
    private String reference;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "order_id", nullable = false)
    private Order order;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "customer_id", nullable = false)
    private Customer customer;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "amount_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "amount_currency", length = 3))
    })
    private Money amount;

    @Enumerated(EnumType.STRING)
    @Column(length = 30, nullable = false)
    private PaymentMethod method;

    @Enumerated(EnumType.STRING)
    @Column(length = 30, nullable = false)
    private PaymentStatus status;

    @Column(nullable = false)
    private boolean isRefund;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "original_payment_id")
    private Payment originalPayment;

    @OneToMany(mappedBy = "originalPayment")
    private List<Payment> refunds;

    @Column(length = 50)
    private String gatewayName;

    @Column(length = 255)
    private String gatewayTransactionId;

    @Column(columnDefinition = "text")
    private String gatewayResponse;

    @Column(columnDefinition = "text")
    private String failureReason;

    @Column(length = 4)
    private String cardLast4;

    @Column(length = 20)
    private String cardBrand;

    @Column(nullable = false)
    private Instant createdAt;

    private Instant completedAt;

    protected Payment() {
    }

    public Payment(String reference, Order order, Customer customer, Money amount,
                   PaymentMethod method, boolean isRefund, Payment originalPayment) {
        this.id = UUID.randomUUID();
        this.reference = reference;
        this.order = order;
        this.customer = customer;
        this.amount = amount;
        this.method = method;
        this.status = PaymentStatus.Pending;
        this.isRefund = isRefund;
        this.originalPayment = originalPayment;
        this.refunds = new ArrayList<>();
        this.createdAt = Instant.now();
    }

    public Payment(String reference, Order order, Customer customer, Money amount, PaymentMethod method) {
        this(reference, order, customer, amount, method, false, null);
    }

    public UUID getId() {
        return id;
    }

    public String getReference() {
        return reference;
    }

    public Order getOrder() {
        return order;
    }

    public Money getAmount() {
        return amount;
    }

    public PaymentMethod getMethod() {
        return method;
    }

    public PaymentStatus getStatus() {
        return status;
    }

    public boolean isRefund() {
        return isRefund;
    }

    public boolean isSuccessful() {
        return status == PaymentStatus.Completed || status == PaymentStatus.PartiallyRefunded;
    }

    public void markProcessing() {
        if (status != PaymentStatus.Pending) {
            throw new IllegalStateException("Can only process pending payments");
        }
        this.status = PaymentStatus.Processing;
    }

    public void complete(String gatewayTransactionId) {
        if (status == PaymentStatus.Completed) {
            return; // Idempotent
        }
        if (status != PaymentStatus.Pending && status != PaymentStatus.Processing) {
            throw new IllegalStateException("Cannot complete payment in status " + status.name());
        }
        this.status = PaymentStatus.Completed;
        this.completedAt = Instant.now();
        this.gatewayTransactionId = gatewayTransactionId;

        if (isRefund && originalPayment != null) {
            originalPayment.updateRefundStatus();
        }
    }

    public void fail(String reason) {
        if (status != PaymentStatus.Pending && status != PaymentStatus.Processing) {
            throw new IllegalStateException("Cannot fail already completed payment");
        }
        this.status = PaymentStatus.Failed;
        this.failureReason = reason;
    }

    public void cancel() {
        if (status != PaymentStatus.Pending) {
            throw new IllegalStateException("Can only cancel pending payments");
        }
        this.status = PaymentStatus.Cancelled;
    }

    public void setCardInfo(String last4, String brand) {
        if (method != PaymentMethod.Card) {
            throw new IllegalStateException("Card info only for card payments");
        }
        this.cardLast4 = last4;
        this.cardBrand = brand;
    }

    public void setGatewayInfo(String name, String transactionId, String response) {
        this.gatewayName = name;
        this.gatewayTransactionId = transactionId;
        this.gatewayResponse = response;
    }

    public boolean canBeRefunded() {
        return (status == PaymentStatus.Completed || status == PaymentStatus.PartiallyRefunded)
                && !isRefund;
    }

    public Money getRefundedAmount() {
        int total = 0;
        for (Payment refund : refunds) {
            if (refund.isSuccessful()) {
                total += refund.getAmount().getCents();
            }
        }
        return new Money(total, amount.getCurrency());
    }

    public Money getRefundableAmount() {
        return amount.subtract(getRefundedAmount());
    }

    public void updateRefundStatus() {
        Money refundable = getRefundableAmount();
        if (refundable.isZeroOrLess()) {
            this.status = PaymentStatus.Refunded;
        } else {
            this.status = PaymentStatus.PartiallyRefunded;
        }
    }

    public static Payment createRefund(Payment originalPayment, Money refundAmount, String reference) {
        if (!originalPayment.canBeRefunded()) {
            throw new IllegalStateException("Payment in status " + originalPayment.status.name() + " cannot be refunded");
        }
        if (refundAmount.getCents() <= 0) {
            throw new IllegalStateException("Refund amount must be positive");
        }
        if (refundAmount.isGreaterThan(originalPayment.getRefundableAmount())) {
            throw new IllegalStateException("Refund exceeds refundable amount");
        }

        Payment refund = new Payment(
                reference,
                originalPayment.order,
                originalPayment.customer,
                refundAmount,
                originalPayment.method,
                true,
                originalPayment
        );
        originalPayment.refunds.add(refund);
        return refund;
    }

    public String getFormattedAmount() {
        String prefix = isRefund ? "-" : "";
        return prefix + amount.format();
    }
}
