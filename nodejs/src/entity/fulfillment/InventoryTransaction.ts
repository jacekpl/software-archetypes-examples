import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { TransactionType } from "../../enum";

// Use a lazy import to avoid circular dependency
import type { InventoryItem } from "./InventoryItem";

@Entity("inventory_transaction")
export class InventoryTransaction {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne("InventoryItem", "transactions")
  @JoinColumn()
  inventoryItem: any; // typed as InventoryItem at runtime

  @Column({ type: "enum", enum: TransactionType })
  type: TransactionType;

  @Column({ type: "int" })
  quantity: number;

  @Column({ type: "int" })
  balanceAfter: number;

  @Column({ type: "varchar", length: 50, nullable: true })
  reference: string | null;

  @Column({ type: "text", nullable: true })
  notes: string | null;

  @ManyToOne(() => InventoryTransaction, { nullable: true })
  @JoinColumn()
  relatedTransaction: InventoryTransaction | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(
    inventoryItem: any,
    type: TransactionType,
    quantity: number,
    balanceAfter: number,
    reference?: string,
    notes?: string,
  ) {
    this.inventoryItem = inventoryItem;
    this.type = type;
    this.quantity = quantity;
    this.balanceAfter = balanceAfter;
    this.reference = reference ?? null;
    this.notes = notes ?? null;
  }
}
