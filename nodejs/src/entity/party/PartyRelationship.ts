import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
  TableInheritance,
} from "typeorm";
import { Party } from "./Party";

@Entity("party_relationship")
@TableInheritance({ column: { name: "relationship_type", type: "varchar" } })
export abstract class PartyRelationship {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Party)
  @JoinColumn({ name: "from_party_id" })
  fromParty: Party;

  @ManyToOne(() => Party)
  @JoinColumn({ name: "to_party_id" })
  toParty: Party;

  @Column({ type: "date" })
  validFrom: string;

  @Column({ type: "date", nullable: true })
  validTo: string | null = null;

  @Column({ type: "text", nullable: true })
  notes: string | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(fromParty: Party, toParty: Party, validFrom: string) {
    if (fromParty && toParty && fromParty.id === toParty.id) {
      throw new Error("Self-relationships not allowed");
    }
    this.fromParty = fromParty;
    this.toParty = toParty;
    this.validFrom = validFrom;
  }

  isActive(): boolean {
    const today = new Date().toISOString().split("T")[0];
    return this.validFrom <= today && (this.validTo === null || this.validTo >= today);
  }

  terminate(endDate: string, reason: string): void {
    if (endDate < this.validFrom) {
      throw new Error("End date cannot be before start date");
    }
    if (this.validTo !== null) {
      throw new Error("Relationship already terminated");
    }
    this.validTo = endDate;
    this.notes = reason;
  }

  getOtherParty(party: Party): Party {
    if (this.fromParty.id === party.id) return this.toParty;
    if (this.toParty.id === party.id) return this.fromParty;
    throw new Error("Party not in relationship");
  }
}
