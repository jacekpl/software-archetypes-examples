import {
  Entity,
  PrimaryColumn,
  Column,
  OneToMany,
  TableInheritance,
} from "typeorm";

@Entity("party")
@TableInheritance({ column: { name: "party_type", type: "varchar" } })
export abstract class Party {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @OneToMany("Address", "party", { cascade: true, eager: false })
  addresses!: any[];

  @OneToMany("PartyRole", "party", { cascade: true })
  roles!: any[];

  @OneToMany("PartyIdentifier", "party", { cascade: true })
  identifiers!: any[];

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  @Column({ type: "timestamptz", default: () => "NOW()" })
  updatedAt: Date = new Date();

  abstract getName(): string;

  getPrimaryAddress(): any | undefined {
    return this.addresses.find((a: any) => a.isPrimary);
  }

  addAddress(address: any): void {
    if (!this.addresses) this.addresses = [];
    if (address.isPrimary) {
      for (const existing of this.addresses) {
        if ((existing as any).type === address.type) {
          (existing as any).isPrimary = false;
        }
      }
    }
    address.party = this;
    this.addresses.push(address);
  }

  addRole(role: any): void {
    if (!this.roles) this.roles = [];
    this.roles.push(role);
  }

  addIdentifier(identifier: any): void {
    if (!this.identifiers) this.identifiers = [];
    identifier.party = this;
    this.identifiers.push(identifier);
  }
}
