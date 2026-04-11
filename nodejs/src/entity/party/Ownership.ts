import { ChildEntity, Column } from "typeorm";
import { PartyRelationship } from "./PartyRelationship";
import { Party } from "./Party";
import { Organization } from "./Organization";

@ChildEntity("ownership")
export class Ownership extends PartyRelationship {
  @Column({ type: "decimal", precision: 5, scale: 2 })
  ownershipPercentage: number;

  @Column({ type: "boolean" })
  votingRights: boolean;

  @Column({ type: "varchar", length: 10, nullable: true })
  shareClass: string | null = null;

  constructor(
    owner: Party,
    owned: Organization,
    validFrom: string,
    ownershipPercentage: number,
    votingRights: boolean = true,
  ) {
    super(owner, owned, validFrom);
    this.ownershipPercentage = ownershipPercentage;
    this.votingRights = votingRights;
  }

  isMajorityOwner(): boolean {
    return Number(this.ownershipPercentage) > 50.0;
  }

  hasVotingRights(): boolean {
    return this.votingRights;
  }

  adjustOwnership(newPercentage: number): void {
    if (newPercentage < 0 || newPercentage > 100) {
      throw new Error("Percentage must be between 0 and 100");
    }
    if (!this.isActive()) {
      throw new Error("Cannot adjust terminated ownership");
    }
    this.ownershipPercentage = newPercentage;
  }
}
