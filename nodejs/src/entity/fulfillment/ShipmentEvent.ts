import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { ShipmentStatus } from "../../enum";

// Forward reference to avoid circular dependency
import type { Shipment } from "./Shipment";

@Entity("shipment_event")
export class ShipmentEvent {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne("Shipment", "events", { onDelete: "CASCADE" })
  @JoinColumn()
  shipment: any; // typed as Shipment at runtime

  @Column({ type: "enum", enum: ShipmentStatus })
  status: ShipmentStatus;

  @Column({ length: 255 })
  description: string;

  @Column({ type: "varchar", length: 100, nullable: true })
  location: string | null;

  @Column({ type: "varchar", length: 50, nullable: true })
  carrierEventCode: string | null = null;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  occurredAt: Date = new Date();

  constructor(shipment: any, status: ShipmentStatus, description: string, location?: string) {
    this.shipment = shipment;
    this.status = status;
    this.description = description;
    this.location = location ?? null;
  }
}
