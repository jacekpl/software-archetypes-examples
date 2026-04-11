package com.archetypes.entity.party;

import com.archetypes.embeddable.Money;
import jakarta.persistence.*;
import java.time.LocalDate;

@Entity
@Table(name = "employee")
@DiscriminatorValue("employee")
public class Employee extends PartyRole {

    @Column(length = 30, unique = true)
    private String employeeId;

    @Column(length = 100, nullable = false)
    private String department;

    @Column(length = 100, nullable = false)
    private String position;

    @Column(nullable = false)
    private LocalDate hireDate;

    @Embedded
    @AttributeOverrides({
            @AttributeOverride(name = "cents", column = @Column(name = "salary_cents")),
            @AttributeOverride(name = "currency", column = @Column(name = "salary_currency", length = 3))
    })
    private Money salary;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "manager_id")
    private Employee manager;

    protected Employee() {
    }

    public Employee(Party party, LocalDate validFrom, String employeeId, String department,
                    String position, LocalDate hireDate) {
        super(party, validFrom);
        this.employeeId = employeeId;
        this.department = department;
        this.position = position;
        this.hireDate = hireDate;
    }

    public String getEmployeeId() {
        return employeeId;
    }

    public String getDepartment() {
        return department;
    }

    public String getPosition() {
        return position;
    }

    public LocalDate getHireDate() {
        return hireDate;
    }

    public Money getSalary() {
        return salary;
    }

    public void setSalary(Money salary) {
        this.salary = salary;
    }

    public Employee getManager() {
        return manager;
    }

    public void setManager(Employee manager) {
        this.manager = manager;
    }

    public void promote(String newPosition, Money newSalary) {
        if (!isActive()) {
            throw new IllegalStateException("Cannot promote terminated employee");
        }
        if (salary != null && newSalary.getCents() < salary.getCents()) {
            throw new IllegalStateException("Salary cannot decrease on promotion");
        }
        this.position = newPosition;
        this.salary = newSalary;
    }
}
