import { ChildEntity, Column, ManyToOne, JoinColumn } from "typeorm";
import { PartyRole } from "./PartyRole";
import { Party } from "./Party";
import { Money } from "../../embeddable/Money";

@ChildEntity("employee")
export class Employee extends PartyRole {
  @Column({ length: 30, unique: true })
  employeeId: string;

  @Column({ length: 100 })
  department: string;

  @Column({ length: 100 })
  position: string;

  @Column({ type: "date" })
  hireDate: string;

  @Column({ name: "salary_cents", type: "int", nullable: true })
  salaryCents: number | null = null;

  @Column({ name: "salary_currency", type: "varchar", length: 3, nullable: true })
  salaryCurrency: string | null = null;

  @ManyToOne(() => Employee, { nullable: true })
  @JoinColumn()
  manager: Employee | null = null;

  constructor(
    party: Party,
    validFrom: string,
    employeeId: string,
    department: string,
    position: string,
    hireDate: string,
  ) {
    super(party, validFrom);
    this.employeeId = employeeId;
    this.department = department;
    this.position = position;
    this.hireDate = hireDate;
  }

  setSalary(cents: number, currency: string): void {
    this.salaryCents = cents;
    this.salaryCurrency = currency;
  }

  getSalary(): Money | null {
    if (this.salaryCents === null || this.salaryCurrency === null) return null;
    return new Money(this.salaryCents, this.salaryCurrency);
  }

  promote(newPosition: string, newSalaryCents: number, newSalaryCurrency: string): void {
    if (!this.isActive()) {
      throw new Error("Cannot promote terminated employee");
    }
    if (this.salaryCents !== null && newSalaryCents < this.salaryCents) {
      throw new Error("Salary cannot decrease on promotion");
    }
    this.position = newPosition;
    this.salaryCents = newSalaryCents;
    this.salaryCurrency = newSalaryCurrency;
  }
}
