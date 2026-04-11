import { ChildEntity, Column } from "typeorm";
import { PartyRole } from "./PartyRole";
import { Party } from "./Party";
import { LoyaltyTier } from "../../enum";

@ChildEntity("customer")
export class Customer extends PartyRole {
  @Column({ length: 30, unique: true })
  customerNumber: string;

  @Column({ type: "enum", enum: LoyaltyTier, nullable: true })
  loyaltyTier: LoyaltyTier | null = null;

  @Column({ name: "credit_limit_cents", type: "int", nullable: true })
  creditLimitCents: number | null = null;

  @Column({ name: "credit_limit_currency", type: "varchar", length: 3, nullable: true })
  creditLimitCurrency: string | null = null;

  constructor(party: Party, validFrom: string, customerNumber: string) {
    super(party, validFrom);
    this.customerNumber = customerNumber;
  }

  setCreditLimit(cents: number, currency: string): void {
    this.creditLimitCents = cents;
    this.creditLimitCurrency = currency;
  }

  getCreditLimitFormatted(): string {
    if (this.creditLimitCents === null || this.creditLimitCurrency === null) return "N/A";
    const formatted = (this.creditLimitCents / 100).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    return `${formatted} ${this.creditLimitCurrency}`;
  }
}
