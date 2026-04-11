package com.archetypes.entity.party;

import com.archetypes.embeddable.Money;
import com.archetypes.enums.SupplierRating;
import jakarta.persistence.*;
import java.time.LocalDate;

@Entity
@Table(name = "supplier")
@DiscriminatorValue("supplier")
public class Supplier extends PartyRole {

    @Column(length = 30, unique = true)
    private String supplierCode;

    @Column(nullable = false)
    private int paymentTermsDays = 30;

    @Enumerated(EnumType.STRING)
    private SupplierRating rating;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "min_order_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "min_order_currency", length = 3))
    })
    private Money minimumOrderValue;

    protected Supplier() {
    }

    public Supplier(Party party, LocalDate validFrom, String supplierCode) {
        super(party, validFrom);
        this.supplierCode = supplierCode;
    }

    public String getSupplierCode() {
        return supplierCode;
    }

    public int getPaymentTermsDays() {
        return paymentTermsDays;
    }

    public void setPaymentTermsDays(int days) {
        this.paymentTermsDays = days;
    }

    public SupplierRating getRating() {
        return rating;
    }

    public void setRating(SupplierRating rating) {
        this.rating = rating;
    }
}
