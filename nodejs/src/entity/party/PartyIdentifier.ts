import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { Party } from "./Party";
import { IdentifierType } from "./IdentifierType";

@Entity("party_identifier")
export class PartyIdentifier {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Party, (party) => party.identifiers, { onDelete: "CASCADE" })
  @JoinColumn()
  party: Party;

  @ManyToOne(() => IdentifierType)
  @JoinColumn({ name: "identifier_type", referencedColumnName: "code" })
  identifierType: IdentifierType;

  @Column({ length: 100 })
  value: string;

  @Column({ type: "varchar", length: 100, nullable: true })
  issuingAuthority: string | null = null;

  @Column({ type: "date", nullable: true })
  validFrom: string | null = null;

  @Column({ type: "date", nullable: true })
  validTo: string | null = null;

  @Column({ type: "boolean", default: false })
  verified: boolean = false;

  @Column({ type: "timestamptz", nullable: true })
  verifiedAt: Date | null = null;

  @Column({ type: "varchar", length: 100, nullable: true })
  verifiedBy: string | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(party: Party, identifierType: IdentifierType, value: string) {
    if (identifierType && party) {
      if (!identifierType.canApplyTo(party)) {
        throw new Error(`${identifierType.code} cannot be assigned to ${party.constructor.name}`);
      }
      if (!identifierType.validate(value)) {
        throw new Error(`Value does not match pattern for ${identifierType.code}`);
      }
    }
    this.party = party;
    this.identifierType = identifierType;
    this.value = value;
  }

  isActive(): boolean {
    const today = new Date().toISOString().split("T")[0];
    const fromOk = this.validFrom === null || this.validFrom <= today;
    const toOk = this.validTo === null || this.validTo >= today;
    return fromOk && toOk;
  }

  isExpired(): boolean {
    if (this.validTo === null) return false;
    const today = new Date().toISOString().split("T")[0];
    return this.validTo < today;
  }

  isVerified(): boolean {
    return this.verified;
  }

  verify(verifier: string): void {
    if (this.verified) {
      throw new Error("Identifier already verified");
    }
    this.verified = true;
    this.verifiedAt = new Date();
    this.verifiedBy = verifier;
  }
}
