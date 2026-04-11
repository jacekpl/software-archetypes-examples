import { ChildEntity, Column } from "typeorm";
import { Party } from "./Party";
import { Gender } from "../../enum";

@ChildEntity("person")
export class Person extends Party {
  @Column({ length: 100 })
  firstName: string;

  @Column({ length: 100 })
  lastName: string;

  @Column({ type: "varchar", length: 100, nullable: true })
  middleName: string | null = null;

  @Column({ type: "date", nullable: true })
  birthDate: string | null = null;

  @Column({ type: "enum", enum: Gender, nullable: true })
  gender: Gender | null = null;

  constructor(firstName: string, lastName: string) {
    super();
    this.firstName = firstName;
    this.lastName = lastName;
  }

  getName(): string {
    if (this.middleName) {
      return `${this.firstName} ${this.middleName} ${this.lastName}`;
    }
    return `${this.firstName} ${this.lastName}`;
  }

  getAge(): number | null {
    if (!this.birthDate) return null;
    const birth = new Date(this.birthDate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  }

  isAdult(): boolean {
    const age = this.getAge();
    return age !== null && age >= 18;
  }
}
