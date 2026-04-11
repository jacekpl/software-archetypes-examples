export class Money {
  constructor(
    public readonly cents: number,
    public readonly currency: string = "PLN",
  ) {}

  add(other: Money): Money {
    if (this.currency !== other.currency) {
      throw new Error(`Cannot add different currencies: ${this.currency} + ${other.currency}`);
    }
    return new Money(this.cents + other.cents, this.currency);
  }

  subtract(other: Money): Money {
    if (this.currency !== other.currency) {
      throw new Error("Cannot subtract different currencies");
    }
    return new Money(this.cents - other.cents, this.currency);
  }

  multiply(factor: number): Money {
    return new Money(this.cents * factor, this.currency);
  }

  isGreaterThan(other: Money): boolean {
    return this.cents > other.cents;
  }

  isGreaterThanOrEqual(other: Money): boolean {
    return this.cents >= other.cents;
  }

  isZeroOrLess(): boolean {
    return this.cents <= 0;
  }

  format(): string {
    const formatted = (this.cents / 100).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return `${formatted} ${this.currency}`;
  }

  toString(): string {
    return this.format();
  }

  static zero(currency = "PLN"): Money {
    return new Money(0, currency);
  }
}
