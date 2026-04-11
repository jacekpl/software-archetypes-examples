package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.time.Instant;
import java.time.LocalDate;
import java.util.UUID;

@Entity
@Table(name = "party_identifier")
public class PartyIdentifier {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "party_id", nullable = false)
    private Party party;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "identifier_type", referencedColumnName = "code", nullable = false)
    private IdentifierType identifierType;

    @Column(length = 100, nullable = false)
    private String value;

    @Column(length = 100)
    private String issuingAuthority;

    private LocalDate validFrom;

    private LocalDate validTo;

    @Column(nullable = false)
    private boolean verified = false;

    private Instant verifiedAt;

    @Column(length = 100)
    private String verifiedBy;

    @Column(nullable = false)
    private Instant createdAt;

    protected PartyIdentifier() {
    }

    public PartyIdentifier(Party party, IdentifierType identifierType, String value) {
        if (!identifierType.canApplyTo(party)) {
            throw new IllegalStateException(identifierType.getCode() + " cannot be assigned to " + party.getClass().getSimpleName());
        }
        if (!identifierType.validate(value)) {
            throw new IllegalStateException("Value does not match pattern for " + identifierType.getCode());
        }

        this.id = UUID.randomUUID();
        this.party = party;
        this.identifierType = identifierType;
        this.value = value;
        this.createdAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public Party getParty() {
        return party;
    }

    public void setParty(Party party) {
        this.party = party;
    }

    public IdentifierType getIdentifierType() {
        return identifierType;
    }

    public String getValue() {
        return value;
    }

    public String getIssuingAuthority() {
        return issuingAuthority;
    }

    public void setIssuingAuthority(String issuingAuthority) {
        this.issuingAuthority = issuingAuthority;
    }

    public void setValidFrom(LocalDate validFrom) {
        this.validFrom = validFrom;
    }

    public void setValidTo(LocalDate validTo) {
        this.validTo = validTo;
    }

    public boolean isActive() {
        LocalDate today = LocalDate.now();
        boolean fromOk = validFrom == null || !validFrom.isAfter(today);
        boolean toOk = validTo == null || !validTo.isBefore(today);
        return fromOk && toOk;
    }

    public boolean isExpired() {
        return validTo != null && validTo.isBefore(LocalDate.now());
    }

    public boolean isVerified() {
        return verified;
    }

    public void verify(String verifier) {
        if (verified) {
            throw new IllegalStateException("Identifier already verified");
        }
        this.verified = true;
        this.verifiedAt = Instant.now();
        this.verifiedBy = verifier;
    }
}
