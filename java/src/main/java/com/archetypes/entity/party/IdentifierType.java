package com.archetypes.entity.party;

import jakarta.persistence.*;
import java.util.regex.Pattern;

@Entity
@Table(name = "identifier_type")
public class IdentifierType {

    @Id
    @Column(length = 30)
    private String code;

    @Column(length = 100, nullable = false)
    private String name;

    @Column(length = 255)
    private String pattern;

    @Column(nullable = false)
    private boolean appliesToPerson;

    @Column(nullable = false)
    private boolean appliesToOrganization;

    @Column(nullable = false)
    private boolean requiresVerification = false;

    protected IdentifierType() {
    }

    public IdentifierType(String code, String name, boolean appliesToPerson,
                          boolean appliesToOrganization, String pattern) {
        this.code = code;
        this.name = name;
        this.appliesToPerson = appliesToPerson;
        this.appliesToOrganization = appliesToOrganization;
        this.pattern = pattern;
    }

    public IdentifierType(String code, String name, boolean appliesToPerson,
                          boolean appliesToOrganization) {
        this(code, name, appliesToPerson, appliesToOrganization, null);
    }

    public String getCode() {
        return code;
    }

    public String getName() {
        return name;
    }

    public boolean validate(String value) {
        if (pattern == null) {
            return true;
        }
        return Pattern.matches(pattern, value);
    }

    public boolean canApplyTo(Party party) {
        if (party instanceof Person) {
            return appliesToPerson;
        }
        if (party instanceof Organization) {
            return appliesToOrganization;
        }
        return false;
    }

    public boolean isRequiresVerification() {
        return requiresVerification;
    }

    public void setRequiresVerification(boolean requiresVerification) {
        this.requiresVerification = requiresVerification;
    }
}
