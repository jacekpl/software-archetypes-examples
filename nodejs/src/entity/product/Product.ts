import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { ProductType } from "./ProductType";
import { ProductInstance } from "./ProductInstance";
import { Money } from "../../embeddable/Money";
import { ProductStatus } from "../../enum";

@Entity("product")
export class Product {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 50, unique: true })
  sku: string;

  @Column({ length: 255 })
  name: string;

  @Column({ type: "text", nullable: true })
  description: string | null = null;

  @ManyToOne(() => ProductType)
  @JoinColumn()
  productType: ProductType;

  @Column({ name: "base_price_cents", type: "int" })
  basePriceCents: number;

  @Column({ name: "base_price_currency", type: "varchar", length: 3 })
  basePriceCurrency: string;

  @Column({ type: "enum", enum: ProductStatus })
  status: ProductStatus;

  @Column({ type: "decimal", precision: 5, scale: 2, default: "23.00" })
  taxRate: number = 23.0;

  @Column({ type: "decimal", precision: 10, scale: 3, nullable: true })
  weightKg: number | null = null;

  @OneToMany(() => ProductInstance, (pi) => pi.product, { cascade: true })
  instances: ProductInstance[];

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  @Column({ type: "timestamptz", default: () => "NOW()" })
  updatedAt: Date = new Date();

  constructor(sku: string, name: string, productType: ProductType, basePrice: Money) {
    this.sku = sku;
    this.name = name;
    this.productType = productType;
    this.basePriceCents = basePrice?.cents ?? 0;
    this.basePriceCurrency = basePrice?.currency ?? "PLN";
    this.status = ProductStatus.Draft;
  }

  getBasePrice(): Money {
    return new Money(this.basePriceCents, this.basePriceCurrency);
  }

  getTaxRate(): number {
    return Number(this.taxRate);
  }

  isAvailable(): boolean {
    return this.status === ProductStatus.Active;
  }

  publish(): void {
    if (this.status !== ProductStatus.Draft) {
      throw new Error("Can only publish draft products");
    }
    this.status = ProductStatus.Active;
    this.updatedAt = new Date();
  }

  discontinue(): void {
    if (this.status !== ProductStatus.Active) {
      throw new Error("Can only discontinue active products");
    }
    this.status = ProductStatus.Discontinued;
    this.updatedAt = new Date();
  }
}
