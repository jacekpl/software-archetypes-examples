package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.time.LocalDate;

@Entity
@DiscriminatorValue("organization")
public class Organization extends Party {

    @Column(length = 255)
    private String legalName;

    @Column(length = 255)
    private String tradingName;

    @Column(length = 50)
    private String registrationNumber;

    @Column(length = 50)
    private String taxId;

    private LocalDate foundedDate;

    protected Organization() {
    }

    public Organization(String legalName) {
        super();
        this.legalName = legalName;
    }

    @Override
    public String getName() {
        return tradingName != null ? tradingName : legalName;
    }

    public String getOfficialName() {
        return legalName;
    }

    public String getLegalName() {
        return legalName;
    }

    public String getTradingName() {
        return tradingName;
    }

    public void setTradingName(String tradingName) {
        this.tradingName = tradingName;
    }

    public String getRegistrationNumber() {
        return registrationNumber;
    }

    public void setRegistrationNumber(String registrationNumber) {
        this.registrationNumber = registrationNumber;
    }

    public String getTaxId() {
        return taxId;
    }

    public void setTaxId(String taxId) {
        this.taxId = taxId;
    }

    public void setFoundedDate(LocalDate foundedDate) {
        this.foundedDate = foundedDate;
    }
}
