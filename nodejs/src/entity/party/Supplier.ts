import { ChildEntity, Column } from "typeorm";
import { PartyRole } from "./PartyRole";
import { Party } from "./Party";
import { SupplierRating } from "../../enum";

@ChildEntity("supplier")
export class Supplier extends PartyRole {
  @Column({ length: 30, unique: true })
  supplierCode: string;

  @Column({ type: "int", default: 30 })
  paymentTermsDays: number = 30;

  @Column({ type: "enum", enum: SupplierRating, nullable: true })
  rating: SupplierRating | null = null;

  @Column({ name: "min_order_cents", type: "int", nullable: true })
  minimumOrderValueCents: number | null = null;

  @Column({ name: "min_order_currency", type: "varchar", length: 3, nullable: true })
  minimumOrderValueCurrency: string | null = null;

  constructor(party: Party, validFrom: string, supplierCode: string) {
    super(party, validFrom);
    this.supplierCode = supplierCode;
  }
}
