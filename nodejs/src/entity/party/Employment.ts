import { ChildEntity, Column } from "typeorm";
import { PartyRelationship } from "./PartyRelationship";
import { Person } from "./Person";
import { Organization } from "./Organization";
import { EmploymentType } from "../../enum";

@ChildEntity("employment")
export class Employment extends PartyRelationship {
  @Column({ length: 100 })
  jobTitle: string;

  @Column({ type: "varchar", length: 100, nullable: true })
  department: string | null = null;

  @Column({ type: "enum", enum: EmploymentType })
  employmentType: EmploymentType;

  @Column({ type: "varchar", length: 100, nullable: true })
  workLocation: string | null = null;

  constructor(
    employee: Person,
    employer: Organization,
    validFrom: string,
    jobTitle: string,
    employmentType: EmploymentType,
  ) {
    super(employee, employer, validFrom);
    this.jobTitle = jobTitle;
    this.employmentType = employmentType;
  }

  getEmployee(): Person {
    return this.fromParty as Person;
  }

  getEmployer(): Organization {
    return this.toParty as Organization;
  }

  promote(newTitle: string, newDepartment?: string): void {
    if (!this.isActive()) {
      throw new Error("Cannot promote in terminated employment");
    }
    this.jobTitle = newTitle;
    if (newDepartment !== undefined) {
      this.department = newDepartment;
    }
  }
}
