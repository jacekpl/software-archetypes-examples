import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { LocationType } from "../../enum";

@Entity("location")
export class Location {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 30, unique: true })
  code: string;

  @Column({ length: 100 })
  name: string;

  @Column({ type: "enum", enum: LocationType })
  type: LocationType;

  @ManyToOne(() => Location, (loc) => loc.children, { nullable: true })
  @JoinColumn()
  parent: Location | null = null;

  @OneToMany(() => Location, (loc) => loc.parent)
  children: Location[];

  @Column({ type: "boolean", default: true })
  isStockable: boolean = true;

  @Column({ type: "boolean", default: true })
  isActive: boolean = true;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(code: string, name: string, type: LocationType, parent?: Location) {
    this.code = code;
    this.name = name;
    this.type = type;
    this.parent = parent ?? null;
  }

  getPath(): string {
    if (this.parent === null) return this.name;
    return this.parent.getPath() + " / " + this.name;
  }

  canStoreInventory(): boolean {
    return this.isStockable && this.isActive;
  }
}
