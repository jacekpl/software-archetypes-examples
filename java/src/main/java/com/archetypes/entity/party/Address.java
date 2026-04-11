package com.archetypes.entity.party;

import com.archetypes.enums.AddressType;
import jakarta.persistence.*;
import java.util.Map;
import java.util.UUID;

@Entity
@Table(name = "address")
public class Address {

    @Id
    @Column(columnDefinition = "uuid")
    private UUID id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "party_id", nullable = false)
    private Party party;

    @Column(length = 255, nullable = false)
    private String street;

    @Column(length = 100, nullable = false)
    private String city;

    @Column(length = 20, nullable = false)
    private String postalCode;

    @Column(length = 2, nullable = false)
    private String country;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private AddressType type;

    @Column(nullable = false)
    private boolean isPrimary;

    protected Address() {
    }

    public Address(String street, String city, String postalCode, String country, AddressType type, boolean isPrimary) {
        this.id = UUID.randomUUID();
        this.street = street;
        this.city = city;
        this.postalCode = postalCode;
        this.country = country;
        this.type = type;
        this.isPrimary = isPrimary;
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

    public String getStreet() {
        return street;
    }

    public String getCity() {
        return city;
    }

    public String getPostalCode() {
        return postalCode;
    }

    public String getCountry() {
        return country;
    }

    public AddressType getType() {
        return type;
    }

    public boolean isPrimary() {
        return isPrimary;
    }

    public void setIsPrimary(boolean isPrimary) {
        this.isPrimary = isPrimary;
    }

    public Map<String, String> toMap() {
        return Map.of(
                "street", street,
                "city", city,
                "postalCode", postalCode,
                "country", country
        );
    }
}
