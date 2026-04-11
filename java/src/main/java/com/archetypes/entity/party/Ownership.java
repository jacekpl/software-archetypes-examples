package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.math.BigDecimal;
import java.time.LocalDate;

@Entity
@Table(name = "ownership")
@DiscriminatorValue("ownership")
public class Ownership extends PartyRelationship {

    @Column(precision = 5, scale = 2, nullable = false)
    private BigDecimal ownershipPercentage;

    @Column(nullable = false)
    private boolean votingRights;

    @Column(length = 10)
    private String shareClass;

    protected Ownership() {
    }

    public Ownership(Party owner, Organization owned, LocalDate validFrom,
                     double ownershipPercentage, boolean votingRights) {
        super(owner, owned, validFrom);
        this.ownershipPercentage = BigDecimal.valueOf(ownershipPercentage);
        this.votingRights = votingRights;
    }

    public double getOwnershipPercentage() {
        return ownershipPercentage.doubleValue();
    }

    public boolean isMajorityOwner() {
        return getOwnershipPercentage() > 50.0;
    }

    public boolean hasVotingRights() {
        return votingRights;
    }

    public String getShareClass() {
        return shareClass;
    }

    public void setShareClass(String shareClass) {
        this.shareClass = shareClass;
    }

    public void adjustOwnership(double newPercentage) {
        if (newPercentage < 0 || newPercentage > 100) {
            throw new IllegalStateException("Percentage must be between 0 and 100");
        }
        if (!isActive()) {
            throw new IllegalStateException("Cannot adjust terminated ownership");
        }
        this.ownershipPercentage = BigDecimal.valueOf(newPercentage);
    }
}
