import { Entity, PrimaryColumn, Column } from "typeorm";

@Entity("carrier")
export class Carrier {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 20, unique: true })
  code: string;

  @Column({ length: 100 })
  name: string;

  @Column({ type: "varchar", length: 255, nullable: true })
  trackingUrlTemplate: string | null = null;

  @Column({ type: "boolean", default: true })
  isActive: boolean = true;

  constructor(code: string, name: string, trackingUrlTemplate?: string) {
    this.code = code;
    this.name = name;
    this.trackingUrlTemplate = trackingUrlTemplate ?? null;
  }

  getTrackingUrl(trackingNumber: string): string | null {
    if (this.trackingUrlTemplate === null) return null;
    return this.trackingUrlTemplate.replace("{tracking}", trackingNumber);
  }
}
