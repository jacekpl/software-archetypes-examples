import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { Product } from "./Product";
import { InstanceStatus } from "../../enum";

@Entity("product_instance")
export class ProductInstance {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Product, (p) => p.instances)
  @JoinColumn()
  product: Product;

  @Column({ type: "varchar", length: 100, unique: true, nullable: true })
  serialNumber: string | null = null;

  @Column({ type: "varchar", length: 50, nullable: true })
  batchNumber: string | null = null;

  @Column({ type: "date", nullable: true })
  manufacturedDate: string | null = null;

  @Column({ type: "date", nullable: true })
  expiryDate: string | null = null;

  @Column({ type: "enum", enum: InstanceStatus })
  status: InstanceStatus;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(product: Product) {
    this.product = product;
    this.status = InstanceStatus.Available;
  }

  isAvailable(): boolean {
    return this.status === InstanceStatus.Available;
  }

  isExpired(): boolean {
    if (!this.expiryDate) return false;
    const today = new Date().toISOString().split("T")[0];
    return this.expiryDate < today;
  }
}
