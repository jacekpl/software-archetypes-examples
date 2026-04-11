import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from "typeorm";
import { ProductType } from "./ProductType";
import { DataType } from "../../enum";

@Entity("attribute_definition")
export class AttributeDefinition {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @ManyToOne(() => ProductType, (pt) => pt.attributeDefinitions, { onDelete: "CASCADE" })
  @JoinColumn()
  productType: ProductType;

  @Column({ length: 50 })
  code: string;

  @Column({ length: 100 })
  name: string;

  @Column({ type: "enum", enum: DataType })
  dataType: DataType;

  @Column({ type: "boolean", default: false })
  required: boolean = false;

  @Column({ type: "text", nullable: true })
  options: string | null = null;

  @Column({ type: "varchar", length: 20, nullable: true })
  unit: string | null = null;

  @Column({ type: "decimal", precision: 15, scale: 4, nullable: true })
  minValue: number | null = null;

  @Column({ type: "decimal", precision: 15, scale: 4, nullable: true })
  maxValue: number | null = null;

  constructor(code: string, name: string, dataType: DataType, required: boolean = false) {
    this.code = code;
    this.name = name;
    this.dataType = dataType;
    this.required = required;
  }

  setRange(min: number | null, max: number | null): void {
    this.minValue = min;
    this.maxValue = max;
  }

  setOptions(options: string[]): void {
    this.options = JSON.stringify(options);
  }
}
