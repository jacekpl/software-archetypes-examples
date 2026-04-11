import { Entity, PrimaryColumn, Column } from "typeorm";

@Entity("identifier_type")
export class IdentifierType {
  @PrimaryColumn({ length: 30 })
  code: string;

  @Column({ length: 100 })
  name: string;

  @Column({ type: "varchar", length: 255, nullable: true })
  pattern: string | null = null;

  @Column({ type: "boolean" })
  appliesToPerson: boolean;

  @Column({ type: "boolean" })
  appliesToOrganization: boolean;

  @Column({ type: "boolean", default: false })
  requiresVerification: boolean = false;

  constructor(
    code: string,
    name: string,
    appliesToPerson: boolean,
    appliesToOrganization: boolean,
    pattern?: string,
  ) {
    this.code = code;
    this.name = name;
    this.appliesToPerson = appliesToPerson;
    this.appliesToOrganization = appliesToOrganization;
    this.pattern = pattern ?? null;
  }

  validate(value: string): boolean {
    if (this.pattern === null) return true;
    return new RegExp(this.pattern).test(value);
  }

  canApplyTo(party: { constructor: { name: string } }): boolean {
    // Use constructor name to avoid circular imports with Party/Person/Organization
    const typeName = party.constructor.name.toLowerCase();
    if (typeName === "person") return this.appliesToPerson;
    if (typeName === "organization") return this.appliesToOrganization;
    return false;
  }
}
