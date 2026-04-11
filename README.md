# Software Archetypes — Working Implementations

Three complete implementations of enterprise patterns (Party, PartyRole, PartyRelationship, PartyIdentifier, Product, Order, Payment, Inventory, Shipment), each with Docker + PostgreSQL 16 and a demo command.

Tutorials: [software-archetypes.opcode.pl](https://software-archetypes.opcode.pl)

## Implementations

| Stack | Directory | Run |
|-------|-----------|-----|
| **PHP 8.3** / Symfony 7 / Doctrine ORM 3 | [`php/`](php/) | `cd php && make up && make demo` |
| **Java 21** / Spring Boot 3.4 / JPA+Hibernate | [`java/`](java/) | `cd java && make up && make demo` |
| **TypeScript** / Node 22 / TypeORM 0.3 | [`nodejs/`](nodejs/) | `cd nodejs && make build && make demo` |

Each demo creates the same scenario: Jan Kowalski (Person) places an order at Acme (Organization), pays by card, stock is reserved and shipped via DHL, and delivered.

## License

MIT License. Pattern concepts based on "Enterprise Patterns and MDA" by Jim Arlow & Ila Neustadt (Addison-Wesley, 2003).
