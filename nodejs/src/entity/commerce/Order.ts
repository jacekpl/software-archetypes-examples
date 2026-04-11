import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { Customer } from "../party/Customer";
import { Product } from "../product/Product";
import { OrderLine } from "./OrderLine";
import { Payment } from "./Payment";
import { Money } from "../../embeddable/Money";
import { OrderStatus } from "../../enum";

@Entity("orders")
export class Order {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 30, unique: true })
  orderNumber: string;

  @ManyToOne(() => Customer)
  @JoinColumn()
  customer: Customer;

  @Column({ type: "enum", enum: OrderStatus })
  status: OrderStatus;

  @Column({ type: "timestamptz" })
  orderDate: Date = new Date();

  @Column({ type: "timestamptz", nullable: true })
  confirmedAt: Date | null = null;

  @Column({ length: 3 })
  currency: string;

  @Column({ type: "jsonb" })
  billingAddress: Record<string, string>;

  @Column({ type: "jsonb", nullable: true })
  shippingAddress: Record<string, string> | null = null;

  @Column({ name: "subtotal_cents", type: "int" })
  subtotalCents: number = 0;

  @Column({ name: "subtotal_currency", type: "varchar", length: 3 })
  subtotalCurrency: string;

  @Column({ name: "discount_total_cents", type: "int" })
  discountTotalCents: number = 0;

  @Column({ name: "discount_total_currency", type: "varchar", length: 3 })
  discountTotalCurrency: string;

  @Column({ name: "tax_total_cents", type: "int" })
  taxTotalCents: number = 0;

  @Column({ name: "tax_total_currency", type: "varchar", length: 3 })
  taxTotalCurrency: string;

  @Column({ name: "shipping_cost_cents", type: "int" })
  shippingCostCents: number = 0;

  @Column({ name: "shipping_cost_currency", type: "varchar", length: 3 })
  shippingCostCurrency: string;

  @Column({ name: "grand_total_cents", type: "int" })
  grandTotalCents: number = 0;

  @Column({ name: "grand_total_currency", type: "varchar", length: 3 })
  grandTotalCurrency: string;

  @Column({ type: "text", nullable: true })
  notes: string | null = null;

  @Column({ type: "timestamptz", nullable: true })
  cancelledAt: Date | null = null;

  @Column({ type: "text", nullable: true })
  cancellationReason: string | null = null;

  @OneToMany(() => OrderLine, (line) => line.order, {
    cascade: true,
    eager: false,
  })
  lines: OrderLine[];

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(
    orderNumber: string,
    customer: Customer,
    currency: string,
    billingAddress: Record<string, string>,
  ) {
    this.orderNumber = orderNumber;
    this.customer = customer;
    this.currency = currency;
    this.billingAddress = billingAddress;
    this.status = OrderStatus.Draft;
    this.subtotalCurrency = currency;
    this.discountTotalCurrency = currency;
    this.taxTotalCurrency = currency;
    this.shippingCostCurrency = currency;
    this.grandTotalCurrency = currency;
  }

  getSubtotal(): Money {
    return new Money(this.subtotalCents, this.subtotalCurrency);
  }

  getTaxTotal(): Money {
    return new Money(this.taxTotalCents, this.taxTotalCurrency);
  }

  getGrandTotal(): Money {
    return new Money(this.grandTotalCents, this.grandTotalCurrency);
  }

  addLine(product: Product, quantity: number, unitPrice: Money): OrderLine {
    if (this.status !== OrderStatus.Draft) {
      throw new Error("Cannot modify confirmed order");
    }
    if (quantity <= 0) {
      throw new Error("Quantity must be positive");
    }

    if (!this.lines) this.lines = [];
    const line = new OrderLine(
      this,
      this.lines.length + 1,
      product,
      quantity,
      unitPrice,
      product.getTaxRate(),
    );
    this.lines.push(line);
    this.recalculateTotals();
    return line;
  }

  recalculateTotals(): void {
    let subtotal = 0;
    let tax = 0;
    for (const line of (this.lines ?? [])) {
      subtotal += line.getLineTotal().cents;
      tax += line.getTaxAmount().cents;
    }
    this.subtotalCents = subtotal;
    this.taxTotalCents = tax;
    this.grandTotalCents = subtotal + tax + this.shippingCostCents - this.discountTotalCents;
  }

  confirm(): void {
    if (this.status !== OrderStatus.Draft) {
      throw new Error("Can only confirm draft orders");
    }
    if (this.lines.length === 0) {
      throw new Error("Order must have at least one line");
    }
    this.status = OrderStatus.Confirmed;
    this.confirmedAt = new Date();
  }

  markPaid(_payment: Payment): void {
    if (this.status !== OrderStatus.Confirmed) {
      throw new Error("Order must be confirmed before payment");
    }
    this.status = OrderStatus.Paid;
  }

  startProcessing(): void {
    if (this.status !== OrderStatus.Paid) {
      throw new Error("Order must be paid before processing");
    }
    this.status = OrderStatus.Processing;
  }

  markShipped(): void {
    if (this.status !== OrderStatus.Processing) {
      throw new Error("Order must be processing to ship");
    }
    this.status = OrderStatus.Shipped;
  }

  markDelivered(): void {
    if (this.status !== OrderStatus.Shipped) {
      throw new Error("Order must be shipped to deliver");
    }
    this.status = OrderStatus.Delivered;
  }

  complete(): void {
    if (this.status !== OrderStatus.Delivered) {
      throw new Error("Order must be delivered to complete");
    }
    this.status = OrderStatus.Completed;
  }

  cancel(reason: string): void {
    if (![OrderStatus.Draft, OrderStatus.Confirmed].includes(this.status)) {
      throw new Error(`Cannot cancel order in status ${this.status}`);
    }
    this.status = OrderStatus.Cancelled;
    this.cancelledAt = new Date();
    this.cancellationReason = reason;
  }
}
