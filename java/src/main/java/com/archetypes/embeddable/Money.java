package com.archetypes.embeddable;

import jakarta.persistence.Column;
import jakarta.persistence.Embeddable;

@Embeddable
public class Money {

    @Column(nullable = false)
    private int cents;

    @Column(length = 3, nullable = false)
    private String currency;

    protected Money() {
    }

    public Money(int cents, String currency) {
        this.cents = cents;
        this.currency = currency;
    }

    public int getCents() {
        return cents;
    }

    public String getCurrency() {
        return currency;
    }

    public Money add(Money other) {
        if (!this.currency.equals(other.currency)) {
            throw new IllegalStateException("Cannot add different currencies: " + this.currency + " + " + other.currency);
        }
        return new Money(this.cents + other.cents, this.currency);
    }

    public Money subtract(Money other) {
        if (!this.currency.equals(other.currency)) {
            throw new IllegalStateException("Cannot subtract different currencies");
        }
        return new Money(this.cents - other.cents, this.currency);
    }

    public Money multiply(int factor) {
        return new Money(this.cents * factor, this.currency);
    }

    public boolean isGreaterThan(Money other) {
        return this.cents > other.cents;
    }

    public boolean isGreaterThanOrEqual(Money other) {
        return this.cents >= other.cents;
    }

    public boolean isZeroOrLess() {
        return this.cents <= 0;
    }

    public String format() {
        double amount = cents / 100.0;
        return String.format("%,.2f %s", amount, currency);
    }

    @Override
    public String toString() {
        return format();
    }

    public static Money zero(String currency) {
        return new Money(0, currency);
    }
}
