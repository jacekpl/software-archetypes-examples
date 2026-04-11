import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { Order } from "./Order";
import { Customer } from "../party/Customer";
import { Money } from "../../embeddable/Money";
import { PaymentMethod, PaymentStatus } from "../../enum";

@Entity("payment")
export class Payment {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 50, unique: true })
  reference: string;

  @ManyToOne(() => Order)
  @JoinColumn()
  order: Order;

  @ManyToOne(() => Customer)
  @JoinColumn()
  customer: Customer;

  @Column({ name: "amount_cents", type: "int" })
  amountCents: number;

  @Column({ name: "amount_currency", type: "varchar", length: 3 })
  amountCurrency: string;

  @Column({ type: "enum", enum: PaymentMethod })
  method: PaymentMethod;

  @Column({ type: "enum", enum: PaymentStatus })
  status: PaymentStatus;

  @Column({ type: "boolean" })
  isRefund: boolean;

  @ManyToOne(() => Payment, (p) => p.refunds, { nullable: true })
  @JoinColumn()
  originalPayment: Payment | null = null;

  @OneToMany(() => Payment, (p) => p.originalPayment)
  refunds: Payment[];

  @Column({ type: "varchar", length: 50, nullable: true })
  gatewayName: string | null = null;

  @Column({ type: "varchar", length: 255, nullable: true })
  gatewayTransactionId: string | null = null;

  @Column({ type: "jsonb", nullable: true })
  gatewayResponse: Record<string, unknown> | null = null;

  @Column({ type: "text", nullable: true })
  failureReason: string | null = null;

  @Column({ type: "varchar", length: 4, nullable: true })
  cardLast4: string | null = null;

  @Column({ type: "varchar", length: 20, nullable: true })
  cardBrand: string | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  @Column({ type: "timestamptz", nullable: true })
  completedAt: Date | null = null;

  constructor(
    reference: string,
    order: Order,
    customer: Customer,
    amount: Money,
    method: PaymentMethod,
    isRefund: boolean = false,
    originalPayment?: Payment,
  ) {
    this.reference = reference;
    this.order = order;
    this.customer = customer;
    this.amountCents = amount?.cents ?? 0;
    this.amountCurrency = amount?.currency ?? "PLN";
    this.method = method;
    this.status = PaymentStatus.Pending;
    this.isRefund = isRefund;
    this.originalPayment = originalPayment ?? null;
  }

  getAmount(): Money {
    return new Money(this.amountCents, this.amountCurrency);
  }

  isSuccessful(): boolean {
    return [PaymentStatus.Completed, PaymentStatus.PartiallyRefunded].includes(this.status);
  }

  markProcessing(): void {
    if (this.status !== PaymentStatus.Pending) {
      throw new Error("Can only process pending payments");
    }
    this.status = PaymentStatus.Processing;
  }

  complete(gatewayTransactionId: string): void {
    if (this.status === PaymentStatus.Completed) return; // Idempotent
    if (![PaymentStatus.Pending, PaymentStatus.Processing].includes(this.status)) {
      throw new Error(`Cannot complete payment in status ${this.status}`);
    }
    this.status = PaymentStatus.Completed;
    this.completedAt = new Date();
    this.gatewayTransactionId = gatewayTransactionId;

    if (this.isRefund && this.originalPayment !== null) {
      this.originalPayment.updateRefundStatus();
    }
  }

  fail(reason: string): void {
    if (![PaymentStatus.Pending, PaymentStatus.Processing].includes(this.status)) {
      throw new Error("Cannot fail already completed payment");
    }
    this.status = PaymentStatus.Failed;
    this.failureReason = reason;
  }

  cancel(): void {
    if (this.status !== PaymentStatus.Pending) {
      throw new Error("Can only cancel pending payments");
    }
    this.status = PaymentStatus.Cancelled;
  }

  setCardInfo(last4: string, brand: string): void {
    if (this.method !== PaymentMethod.Card) {
      throw new Error("Card info only for card payments");
    }
    this.cardLast4 = last4;
    this.cardBrand = brand;
  }

  setGatewayInfo(name: string, transactionId: string, response?: Record<string, unknown>): void {
    this.gatewayName = name;
    this.gatewayTransactionId = transactionId;
    this.gatewayResponse = response ?? null;
  }

  canBeRefunded(): boolean {
    return [PaymentStatus.Completed, PaymentStatus.PartiallyRefunded].includes(this.status)
      && !this.isRefund;
  }

  getRefundedAmount(): Money {
    let total = 0;
    for (const refund of (this.refunds ?? [])) {
      if (refund.isSuccessful()) {
        total += refund.amountCents;
      }
    }
    return new Money(total, this.amountCurrency);
  }

  getRefundableAmount(): Money {
    return this.getAmount().subtract(this.getRefundedAmount());
  }

  updateRefundStatus(): void {
    const refundable = this.getRefundableAmount();
    if (refundable.isZeroOrLess()) {
      this.status = PaymentStatus.Refunded;
    } else {
      this.status = PaymentStatus.PartiallyRefunded;
    }
  }

  getFormattedAmount(): string {
    const prefix = this.isRefund ? "-" : "";
    return prefix + this.getAmount().format();
  }

  static createRefund(originalPayment: Payment, refundAmount: Money, reference: string): Payment {
    if (!originalPayment.canBeRefunded()) {
      throw new Error(`Payment in status ${originalPayment.status} cannot be refunded`);
    }
    if (refundAmount.cents <= 0) {
      throw new Error("Refund amount must be positive");
    }
    if (refundAmount.isGreaterThan(originalPayment.getRefundableAmount())) {
      throw new Error("Refund exceeds refundable amount");
    }

    const refund = new Payment(
      reference,
      originalPayment.order,
      originalPayment.customer,
      refundAmount,
      originalPayment.method,
      true,
      originalPayment,
    );

    if (!originalPayment.refunds) {
      originalPayment.refunds = [];
    }
    originalPayment.refunds.push(refund);

    return refund;
  }
}
