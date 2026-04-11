import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { Product } from "../product/Product";
import { Location } from "./Location";
import { InventoryTransaction } from "./InventoryTransaction";
import { TransactionType } from "../../enum";

@Entity("inventory_item")
export class InventoryItem {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Product)
  @JoinColumn()
  product: Product;

  @ManyToOne(() => Location)
  @JoinColumn()
  location: Location;

  @Column({ type: "int", default: 0 })
  quantityOnHand: number = 0;

  @Column({ type: "int", default: 0 })
  quantityReserved: number = 0;

  @Column({ type: "int", nullable: true })
  reorderLevel: number | null = null;

  @Column({ type: "int", nullable: true })
  maxLevel: number | null = null;

  @OneToMany(() => InventoryTransaction, (tx) => tx.inventoryItem, {
    cascade: true,
  })
  transactions: InventoryTransaction[];

  @Column({ type: "timestamptz", default: () => "NOW()" })
  updatedAt: Date = new Date();

  constructor(product: Product, location: Location) {
    this.product = product;
    this.location = location;
  }

  getQuantityAvailable(): number {
    return this.quantityOnHand - this.quantityReserved;
  }

  needsReorder(): boolean {
    return this.reorderLevel !== null && this.quantityOnHand <= this.reorderLevel;
  }

  receive(quantity: number, reference?: string, notes?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Receive quantity must be positive");
    this.quantityOnHand += quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Receipt, quantity, reference, notes);
  }

  issue(quantity: number, reference?: string, notes?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Issue quantity must be positive");
    if (quantity > this.quantityOnHand) {
      throw new Error(`Cannot issue ${quantity}, only ${this.quantityOnHand} on hand`);
    }
    this.quantityOnHand -= quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Issue, -quantity, reference, notes);
  }

  reserve(quantity: number, reference?: string, notes?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Reserve quantity must be positive");
    if (quantity > this.getQuantityAvailable()) {
      throw new Error(`Cannot reserve ${quantity}, only ${this.getQuantityAvailable()} available`);
    }
    this.quantityReserved += quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Reserve, quantity, reference, notes);
  }

  release(quantity: number, reference?: string, notes?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Release quantity must be positive");
    if (quantity > this.quantityReserved) {
      throw new Error(`Cannot release ${quantity}, only ${this.quantityReserved} reserved`);
    }
    this.quantityReserved -= quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Release, -quantity, reference, notes);
  }

  shipReserved(quantity: number, reference?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Ship quantity must be positive");
    if (quantity > this.quantityReserved) {
      throw new Error(`Cannot ship ${quantity}, only ${this.quantityReserved} reserved`);
    }
    this.quantityOnHand -= quantity;
    this.quantityReserved -= quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Issue, -quantity, reference, "Shipped from reserved stock");
  }

  adjust(quantity: number, reference?: string, notes?: string): InventoryTransaction {
    const newOnHand = this.quantityOnHand + quantity;
    if (newOnHand < 0) throw new Error("Adjustment would result in negative stock");
    this.quantityOnHand = newOnHand;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.Adjustment, quantity, reference, notes);
  }

  transferOut(quantity: number, destinationCode: string, reference?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Transfer quantity must be positive");
    if (quantity > this.getQuantityAvailable()) {
      throw new Error("Insufficient available stock for transfer");
    }
    this.quantityOnHand -= quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.TransferOut, -quantity, reference, `Transfer to ${destinationCode}`);
  }

  transferIn(quantity: number, sourceCode: string, reference?: string): InventoryTransaction {
    if (quantity <= 0) throw new Error("Transfer quantity must be positive");
    this.quantityOnHand += quantity;
    this.updatedAt = new Date();
    return this.createTransaction(TransactionType.TransferIn, quantity, reference, `Transfer from ${sourceCode}`);
  }

  private createTransaction(
    type: TransactionType,
    quantity: number,
    reference?: string,
    notes?: string,
  ): InventoryTransaction {
    const tx = new InventoryTransaction(this, type, quantity, this.quantityOnHand, reference, notes);
    if (!this.transactions) this.transactions = [];
    this.transactions.push(tx);
    return tx;
  }
}
