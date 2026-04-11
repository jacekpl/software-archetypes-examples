package com.archetypes.entity.party;

import com.archetypes.enums.EmploymentType;
import jakarta.persistence.*;
import java.time.LocalDate;

@Entity
@Table(name = "employment")
@DiscriminatorValue("employment")
public class Employment extends PartyRelationship {

    @Column(length = 100)
    private String jobTitle;

    @Column(length = 100)
    private String department;

    @Enumerated(EnumType.STRING)
    @Column(length = 20, nullable = false)
    private EmploymentType employmentType;

    @Column(length = 100)
    private String workLocation;

    protected Employment() {
    }

    public Employment(Person employee, Organization employer, LocalDate validFrom,
                      String jobTitle, EmploymentType employmentType) {
        super(employee, employer, validFrom);
        this.jobTitle = jobTitle;
        this.employmentType = employmentType;
    }

    public Person getEmployee() {
        return (Person) getFromParty();
    }

    public Organization getEmployer() {
        return (Organization) getToParty();
    }

    public String getJobTitle() {
        return jobTitle;
    }

    public String getDepartment() {
        return department;
    }

    public void setDepartment(String department) {
        this.department = department;
    }

    public EmploymentType getEmploymentType() {
        return employmentType;
    }

    public String getWorkLocation() {
        return workLocation;
    }

    public void setWorkLocation(String workLocation) {
        this.workLocation = workLocation;
    }

    public void promote(String newTitle, String newDepartment) {
        if (!isActive()) {
            throw new IllegalStateException("Cannot promote in terminated employment");
        }
        this.jobTitle = newTitle;
        if (newDepartment != null) {
            this.department = newDepartment;
        }
    }
}
