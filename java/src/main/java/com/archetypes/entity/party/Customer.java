package com.archetypes.entity.party;

import com.archetypes.embeddable.Money;
import com.archetypes.enums.LoyaltyTier;
import jakarta.persistence.*;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;

@Entity
@Table(name = "customer")
@DiscriminatorValue("customer")
public class Customer extends PartyRole {

    @Column(length = 30, unique = true)
    private String customerNumber;

    @Enumerated(EnumType.STRING)
    private LoyaltyTier loyaltyTier;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "credit_limit_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "credit_limit_currency", length = 3))
    })
    private Money creditLimit;

    @OneToMany(mappedBy = "customer")
    private List<com.archetypes.entity.commerce.Order> orders;

    protected Customer() {
    }

    public Customer(Party party, LocalDate validFrom, String customerNumber) {
        super(party, validFrom);
        this.customerNumber = customerNumber;
        this.orders = new ArrayList<>();
    }

    public String getCustomerNumber() {
        return customerNumber;
    }

    public LoyaltyTier getLoyaltyTier() {
        return loyaltyTier;
    }

    public void setLoyaltyTier(LoyaltyTier loyaltyTier) {
        this.loyaltyTier = loyaltyTier;
    }

    public Money getCreditLimit() {
        return creditLimit;
    }

    public void setCreditLimit(Money creditLimit) {
        this.creditLimit = creditLimit;
    }

    public List<com.archetypes.entity.commerce.Order> getOrders() {
        return orders;
    }
}
