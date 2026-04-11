import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
  TableInheritance,
} from "typeorm";
import { Party } from "./Party";

@Entity("party_role")
@TableInheritance({ column: { name: "role_type", type: "varchar" } })
export abstract class PartyRole {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Party, (party) => party.roles)
  @JoinColumn()
  party: Party;

  @Column({ type: "date" })
  validFrom: string;

  @Column({ type: "date", nullable: true })
  validTo: string | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(party: Party, validFrom: string) {
    this.party = party;
    this.validFrom = validFrom;
    if (party) {
      party.addRole(this);
    }
  }

  isActive(): boolean {
    const today = new Date().toISOString().split("T")[0];
    return this.validFrom <= today && (this.validTo === null || this.validTo >= today);
  }

  terminate(endDate: string): void {
    if (endDate < this.validFrom) {
      throw new Error("End date cannot be before start date");
    }
    if (this.validTo !== null) {
      throw new Error("Role already terminated");
    }
    this.validTo = endDate;
  }
}
