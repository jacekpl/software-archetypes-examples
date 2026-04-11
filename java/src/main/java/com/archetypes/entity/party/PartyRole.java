package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.time.Instant;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Table(name = "party_role")
@Inheritance(strategy = InheritanceType.JOINED)
@DiscriminatorColumn(name = "role_type", length = 30)
public abstract class PartyRole {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "party_id", nullable = false)
    private Party party;

    @Column(nullable = false)
    private LocalDate validFrom;

    private LocalDate validTo;

    @Column(nullable = false)
    private Instant createdAt;

    protected PartyRole() {
    }

    public PartyRole(Party party, LocalDate validFrom) {
        this.id = UUID.randomUUID();
        this.party = party;
        this.validFrom = validFrom;
        this.createdAt = Instant.now();
        party.addRole(this);
    }

    public UUID getId() {
        return id;
    }

    public Party getParty() {
        return party;
    }

    public LocalDate getValidFrom() {
        return validFrom;
    }

    public LocalDate getValidTo() {
        return validTo;
    }

    public boolean isActive() {
        LocalDate today = LocalDate.now();
        return !validFrom.isAfter(today) && (validTo == null || !validTo.isBefore(today));
    }

    public void terminate(LocalDate endDate) {
        if (endDate.isBefore(validFrom)) {
            throw new IllegalStateException("End date cannot be before start date");
        }
        if (validTo != null) {
            throw new IllegalStateException("Role already terminated");
        }
        this.validTo = endDate;
    }
}
