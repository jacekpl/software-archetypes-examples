import {
  Entity,
  PrimaryColumn,
  Column,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from "typeorm";
import { AttributeDefinition } from "./AttributeDefinition";

@Entity("product_type")
export class ProductType {
  @PrimaryColumn("uuid")
  id: string = crypto.randomUUID();

  @Column({ length: 50, unique: true })
  code: string;

  @Column({ length: 100 })
  name: string;

  @Column({ type: "text", nullable: true })
  description: string | null = null;

  @ManyToOne(() => ProductType, (pt) => pt.children, { nullable: true })
  @JoinColumn()
  parent: ProductType | null = null;

  @OneToMany(() => ProductType, (pt) => pt.parent)
  children: ProductType[];

  @OneToMany(() => AttributeDefinition, (ad) => ad.productType, {
    cascade: true,
  })
  attributeDefinitions: AttributeDefinition[];

  @Column({ type: "boolean", default: true })
  active: boolean = true;

  @Column({ type: "timestamptz", default: () => "NOW()" })
  createdAt: Date = new Date();

  constructor(code: string, name: string, parent?: ProductType) {
    this.code = code;
    this.name = name;
    this.parent = parent ?? null;
  }

  isLeaf(): boolean {
    return !this.children || this.children.length === 0;
  }

  isRoot(): boolean {
    return this.parent === null;
  }

  getPath(): string[] {
    if (this.parent === null) return [this.name];
    return [...this.parent.getPath(), this.name];
  }

  addAttributeDefinition(definition: AttributeDefinition): void {
    if (!this.attributeDefinitions) this.attributeDefinitions = [];
    definition.productType = this;
    this.attributeDefinitions.push(definition);
  }

  getAllAttributeDefinitions(): AttributeDefinition[] {
    const inherited = this.parent?.getAllAttributeDefinitions() ?? [];
    return [...inherited, ...(this.attributeDefinitions ?? [])];
  }
}
