package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.time.Instant;
import java.util.ArrayList;
import java.util.List;
import java.util.UUID;

@Entity
@Table(name = "party")
@Inheritance(strategy = InheritanceType.SINGLE_TABLE)
@DiscriminatorColumn(name = "party_type", length = 20)
public abstract class Party {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @OneToMany(mappedBy = "party", cascade = CascadeType.ALL, orphanRemoval = true)
    private List<Address> addresses;

    @OneToMany(mappedBy = "party", cascade = CascadeType.PERSIST)
    private List<PartyRole> roles;

    @OneToMany(mappedBy = "party", cascade = {CascadeType.PERSIST, CascadeType.REMOVE})
    private List<PartyIdentifier> identifiers;

    @Column(nullable = false)
    private Instant createdAt;

    @Column(nullable = false)
    private Instant updatedAt;

    protected Party() {
        this.id = UUID.randomUUID();
        this.addresses = new ArrayList<>();
        this.roles = new ArrayList<>();
        this.identifiers = new ArrayList<>();
        this.createdAt = Instant.now();
        this.updatedAt = Instant.now();
    }

    public UUID getId() {
        return id;
    }

    public abstract String getName();

    public List<Address> getAddresses() {
        return addresses;
    }

    public Address getPrimaryAddress() {
        return addresses.stream()
                .filter(Address::isPrimary)
                .findFirst()
                .orElse(null);
    }

    public void addAddress(Address address) {
        if (address.isPrimary()) {
            for (Address existing : addresses) {
                if (existing.getType() == address.getType()) {
                    existing.setIsPrimary(false);
                }
            }
        }
        address.setParty(this);
        this.addresses.add(address);
    }

    public List<PartyRole> getRoles() {
        return roles;
    }

    public void addRole(PartyRole role) {
        this.roles.add(role);
    }

    public List<PartyIdentifier> getIdentifiers() {
        return identifiers;
    }

    public void addIdentifier(PartyIdentifier identifier) {
        identifier.setParty(this);
        this.identifiers.add(identifier);
    }

    public Instant getCreatedAt() {
        return createdAt;
    }
}
