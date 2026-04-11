import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { Order } from "../commerce/Order";
import { OrderLine } from "../commerce/OrderLine";
import { Product } from "../product/Product";
import { Carrier } from "./Carrier";
import { ShipmentItem } from "./ShipmentItem";
import { ShipmentEvent } from "./ShipmentEvent";
import { ShipmentStatus, isShipmentStatusFinal } from "../../enum";

@Entity("shipment")
export class Shipment {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 50, unique: true })
  shipmentNumber: string;

  @ManyToOne(() => Order, { nullable: true })
  @JoinColumn()
  order: Order | null = null;

  @ManyToOne(() => Carrier, { nullable: true })
  @JoinColumn()
  carrier: Carrier | null = null;

  @Column({ type: "varchar", length: 100, nullable: true })
  trackingNumber: string | null = null;

  @Column({ type: "enum", enum: ShipmentStatus })
  status: ShipmentStatus;

  @Column({ type: "jsonb" })
  shipFromAddress: Record<string, string>;

  @Column({ type: "jsonb" })
  shipToAddress: Record<string, string>;

  @Column({ type: "int", nullable: true })
  weightGrams: number | null = null;

  @Column({ name: "shipping_cost_cents", type: "int", default: 0 })
  shippingCostCents: number = 0;

  @Column({ name: "shipping_cost_currency", type: "varchar", length: 3, default: "PLN" })
  shippingCostCurrency: string = "PLN";

  @Column({ type: "jsonb", nullable: true })
  proofOfDelivery: Record<string, string> | null = null;

  @OneToMany(() => ShipmentItem, (item) => item.shipment, {
    cascade: true,
  })
  items: ShipmentItem[];

  @OneToMany(() => ShipmentEvent, (event) => event.shipment, {
    cascade: true,
  })
  events: ShipmentEvent[];

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  @Column({ type: "timestamptz", nullable: true })
  shippedAt: Date | null = null;

  @Column({ type: "timestamptz", nullable: true })
  deliveredAt: Date | null = null;

  // Private — use static factory methods instead
  constructor() {}

  private init(shipmentNumber: string, shipFromAddress: Record<string, string>, shipToAddress: Record<string, string>): void {
    this.shipmentNumber = shipmentNumber;
    this.shipFromAddress = shipFromAddress;
    this.shipToAddress = shipToAddress;
    this.status = ShipmentStatus.Created;
    this.addEvent(ShipmentStatus.Created, "Shipment created");
  }

  static create(shipmentNumber: string, shipFromAddress: Record<string, string>, shipToAddress: Record<string, string>): Shipment {
    const s = new Shipment();
    s.init(shipmentNumber, shipFromAddress, shipToAddress);
    return s;
  }

  static fromOrder(order: Order, shipmentNumber: string, shipFromAddress: Record<string, string>): Shipment {
    const shipment = Shipment.create(shipmentNumber, shipFromAddress, order.shippingAddress ?? {});
    shipment.order = order;
    return shipment;
  }

  addItem(product: Product, quantity: number): ShipmentItem {
    if (!this.items) this.items = [];
    const item = new ShipmentItem(this, product, quantity);
    this.items.push(item);
    return item;
  }

  addItemFromOrderLine(orderLine: OrderLine, quantity: number): ShipmentItem {
    const item = this.addItem(orderLine.product!, quantity);
    item.orderLine = orderLine;
    return item;
  }

  assignCarrier(carrier: Carrier, trackingNumber: string): void {
    this.carrier = carrier;
    this.trackingNumber = trackingNumber;
  }

  // === Status transitions ===

  startPicking(): void {
    this.requireStatus(ShipmentStatus.Created);
    this.updateStatus(ShipmentStatus.Picking, "Picking started");
  }

  markPacked(): void {
    this.requireStatus(ShipmentStatus.Picking);
    this.updateStatus(ShipmentStatus.Packed, "Packed");
  }

  markLabelPrinted(): void {
    this.requireStatus(ShipmentStatus.Packed);
    if (this.carrier === null) {
      throw new Error("Carrier must be assigned before printing label");
    }
    this.updateStatus(ShipmentStatus.LabelPrinted, "Label printed");
  }

  handOverToCarrier(): void {
    this.requireStatus(ShipmentStatus.LabelPrinted);
    this.shippedAt = new Date();
    this.updateStatus(ShipmentStatus.HandedOver, "Handed to " + this.carrier!.name);
  }

  markInTransit(location?: string): void {
    if (![ShipmentStatus.HandedOver, ShipmentStatus.InTransit].includes(this.status)) {
      throw new Error("Shipment not yet handed to carrier");
    }
    this.updateStatus(ShipmentStatus.InTransit, "In transit", location);
  }

  markOutForDelivery(): void {
    this.requireStatus(ShipmentStatus.InTransit);
    this.updateStatus(ShipmentStatus.OutForDelivery, "Out for delivery");
  }

  markDelivered(proof?: Record<string, string>): void {
    this.requireStatus(ShipmentStatus.OutForDelivery);
    this.deliveredAt = new Date();
    this.proofOfDelivery = proof ?? null;
    this.updateStatus(ShipmentStatus.Delivered, "Delivered");
  }

  markDeliveryFailed(reason: string): void {
    this.requireStatus(ShipmentStatus.OutForDelivery);
    this.updateStatus(ShipmentStatus.DeliveryFailed, "Failed: " + reason);
  }

  cancel(reason: string): void {
    if (isShipmentStatusFinal(this.status)) {
      throw new Error("Cannot cancel shipment in final status");
    }
    this.updateStatus(ShipmentStatus.Cancelled, "Cancelled: " + reason);
  }

  getTrackingUrl(): string | null {
    if (this.carrier === null || this.trackingNumber === null) return null;
    return this.carrier.getTrackingUrl(this.trackingNumber);
  }

  getTransitDays(): number | null {
    if (this.shippedAt === null || this.deliveredAt === null) return null;
    const diffMs = this.deliveredAt.getTime() - this.shippedAt.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24));
  }

  // === Helpers ===

  private requireStatus(expected: ShipmentStatus): void {
    if (this.status !== expected) {
      throw new Error(`Expected status ${expected}, got ${this.status}`);
    }
  }

  private updateStatus(newStatus: ShipmentStatus, description: string, location?: string): void {
    this.status = newStatus;
    this.addEvent(newStatus, description, location);
  }

  private addEvent(status: ShipmentStatus, description: string, location?: string): ShipmentEvent {
    if (!this.events) this.events = [];
    const event = new ShipmentEvent(this, status, description, location);
    this.events.push(event);
    return event;
  }
}
