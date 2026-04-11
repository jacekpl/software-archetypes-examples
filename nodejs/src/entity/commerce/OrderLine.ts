import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { Order } from "./Order";
import { Product } from "../product/Product";
import { Money } from "../../embeddable/Money";

@Entity("order_line")
export class OrderLine {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => Order, (order) => order.lines, { onDelete: "CASCADE" })
  @JoinColumn()
  order: Order;

  @Column({ type: "int" })
  lineNumber: number;

  @ManyToOne(() => Product, { nullable: true })
  @JoinColumn()
  product: Product | null;

  @Column({ length: 255 })
  productName: string;

  @Column({ length: 50 })
  productSku: string;

  @Column({ type: "int" })
  quantity: number;

  @Column({ name: "unit_price_cents", type: "int" })
  unitPriceCents: number;

  @Column({ name: "unit_price_currency", type: "varchar", length: 3 })
  unitPriceCurrency: string;

  @Column({ name: "discount_cents", type: "int" })
  discountCents: number = 0;

  @Column({ name: "discount_currency", type: "varchar", length: 3 })
  discountCurrency: string;

  @Column({ type: "decimal", precision: 5, scale: 2 })
  taxRate: number;

  @Column({ name: "tax_amount_cents", type: "int" })
  taxAmountCents: number = 0;

  @Column({ name: "tax_amount_currency", type: "varchar", length: 3 })
  taxAmountCurrency: string;

  @Column({ name: "line_total_cents", type: "int" })
  lineTotalCents: number = 0;

  @Column({ name: "line_total_currency", type: "varchar", length: 3 })
  lineTotalCurrency: string;

  constructor(
    order: Order,
    lineNumber: number,
    product: Product,
    quantity: number,
    unitPrice: Money,
    taxRate: number,
  ) {
    this.order = order;
    this.lineNumber = lineNumber;
    this.product = product;
    this.productName = product?.name ?? "";
    this.productSku = product?.sku ?? "";
    this.quantity = quantity;
    this.unitPriceCents = unitPrice?.cents ?? 0;
    this.unitPriceCurrency = unitPrice?.currency ?? "PLN";
    this.discountCents = 0;
    this.discountCurrency = unitPrice?.currency ?? "PLN";
    this.taxRate = taxRate;
    this.taxAmountCurrency = unitPrice?.currency ?? "PLN";
    this.lineTotalCurrency = unitPrice?.currency ?? "PLN";
    this.recalculate();
  }

  getUnitPrice(): Money {
    return new Money(this.unitPriceCents, this.unitPriceCurrency);
  }

  getLineTotal(): Money {
    return new Money(this.lineTotalCents, this.lineTotalCurrency);
  }

  getTaxAmount(): Money {
    return new Money(this.taxAmountCents, this.taxAmountCurrency);
  }

  recalculate(): void {
    const lineCents = this.unitPriceCents * this.quantity - this.discountCents;
    const taxCents = Math.round(lineCents * Number(this.taxRate) / 100);
    this.lineTotalCents = lineCents;
    this.taxAmountCents = taxCents;
  }
}
