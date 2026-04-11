package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.time.Instant;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Table(name = "party_relationship")
@Inheritance(strategy = InheritanceType.JOINED)
@DiscriminatorColumn(name = "relationship_type", length = 30)
public abstract class PartyRelationship {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "from_party_id", nullable = false)
    private Party fromParty;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "to_party_id", nullable = false)
    private Party toParty;

    @Column(nullable = false)
    private LocalDate validFrom;

    private LocalDate validTo;

    @Column(columnDefinition = "text")
    private String notes;

    @Column(nullable = false)
    private Instant createdAt;

    protected PartyRelationship() {
    }

    public PartyRelationship(Party fromParty, Party toParty, LocalDate validFrom) {
        if (fromParty.getId().equals(toParty.getId())) {
            throw new IllegalStateException("Self-relationships not allowed");
        }
        this.id = UUID.randomUUID();
        this.fromParty = fromParty;
        this.toParty = toParty;
        this.validFrom = validFrom;
        this.createdAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public Party getFromParty() {
        return fromParty;
    }

    public Party getToParty() {
        return toParty;
    }

    public LocalDate getValidFrom() {
        return validFrom;
    }

    public LocalDate getValidTo() {
        return validTo;
    }

    public String getNotes() {
        return notes;
    }

    public void setNotes(String notes) {
        this.notes = notes;
    }

    public boolean isActive() {
        LocalDate today = LocalDate.now();
        return !validFrom.isAfter(today) && (validTo == null || !validTo.isBefore(today));
    }

    public void terminate(LocalDate endDate, String reason) {
        if (endDate.isBefore(validFrom)) {
            throw new IllegalStateException("End date cannot be before start date");
        }
        if (validTo != null) {
            throw new IllegalStateException("Relationship already terminated");
        }
        this.validTo = endDate;
        this.notes = reason;
    }

    public Party getOtherParty(Party party) {
        if (fromParty.getId().equals(party.getId())) {
            return toParty;
        }
        if (toParty.getId().equals(party.getId())) {
            return fromParty;
        }
        throw new IllegalStateException("Party not in relationship");
    }
}
