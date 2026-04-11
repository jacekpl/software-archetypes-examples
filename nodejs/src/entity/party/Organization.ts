import { ChildEntity, Column } from "typeorm";
import { Party } from "./Party";

@ChildEntity("organization")
export class Organization extends Party {
  @Column({ length: 255 })
  legalName: string;

  @Column({ type: "varchar", length: 255, nullable: true })
  tradingName: string | null = null;

  @Column({ type: "varchar", length: 50, nullable: true })
  registrationNumber: string | null = null;

  @Column({ type: "varchar", length: 50, nullable: true })
  taxId: string | null = null;

  @Column({ type: "date", nullable: true })
  foundedDate: string | null = null;

  constructor(legalName: string) {
    super();
    this.legalName = legalName;
  }

  getName(): string {
    return this.tradingName ?? this.legalName;
  }

  getOfficialName(): string {
    return this.legalName;
  }
}
