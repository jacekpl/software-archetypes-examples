import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { Party } from "./Party";
import { AddressType } from "../../enum";

@Entity("address")
export class Address {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Party, (party) => party.addresses, { onDelete: "CASCADE" })
  @JoinColumn()
  party: Party;

  @Column({ length: 255 })
  street: string;

  @Column({ length: 100 })
  city: string;

  @Column({ length: 20 })
  postalCode: string;

  @Column({ length: 2 })
  country: string;

  @Column({ type: "enum", enum: AddressType })
  type: AddressType;

  @Column({ type: "boolean" })
  isPrimary: boolean;

  constructor(
    street: string,
    city: string,
    postalCode: string,
    country: string,
    type: AddressType,
    isPrimary: boolean = false,
  ) {
    this.street = street;
    this.city = city;
    this.postalCode = postalCode;
    this.country = country;
    this.type = type;
    this.isPrimary = isPrimary;
  }

  toObject(): Record<string, string> {
    return {
      street: this.street,
      city: this.city,
      postalCode: this.postalCode,
      country: this.country,
    };
  }
}
