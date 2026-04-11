import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { Product } from "../product/Product";
import { OrderLine } from "../commerce/OrderLine";

// Forward reference to avoid circular dependency
import type { Shipment } from "./Shipment";

@Entity("shipment_item")
export class ShipmentItem {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne("Shipment", "items", { onDelete: "CASCADE" })
  @JoinColumn()
  shipment: any; // typed as Shipment at runtime

  @ManyToOne(() => OrderLine, { nullable: true })
  @JoinColumn()
  orderLine: OrderLine | null = null;

  @ManyToOne(() => Product)
  @JoinColumn()
  product: Product;

  @Column({ type: "int" })
  quantity: number;

  @Column({ type: "varchar", length: 100, nullable: true })
  serialNumber: string | null = null;

  @Column({ type: "varchar", length: 50, nullable: true })
  batchNumber: string | null = null;

  constructor(shipment: any, product: Product, quantity: number) {
    this.shipment = shipment;
    this.product = product;
    this.quantity = quantity;
  }
}
