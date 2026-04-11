# Software Archetypes — Working Implementations

Three production-grade implementations of enterprise patterns (Party, PartyRole, PartyRelationship, PartyIdentifier, Product, Order, Payment, Inventory, Shipment). Each runs with Docker + PostgreSQL 16 and includes a demo command that walks through a complete e-commerce scenario — from customer registration through order placement, payment processing, inventory management, to shipment delivery.

**Tutorials**: [software-archetypes.opcode.pl](https://software-archetypes.opcode.pl)

## Implementations

| Stack | Directory | Run |
|-------|-----------|-----|
| **PHP 8.3** / Symfony 7 / Doctrine ORM 3 | [`php/`](php/) | `cd php && make up && make demo` |
| **Java 21** / Spring Boot 3.4 / JPA+Hibernate | [`java/`](java/) | `cd java && make up && make demo` |
| **TypeScript** / Node 22 / TypeORM 0.3 | [`nodejs/`](nodejs/) | `cd nodejs && make build && make demo` |

Each demo creates the same scenario: Jan Kowalski (Person) places an order at Acme (Organization), pays by card, stock is reserved and shipped via DHL, and delivered — exercising all 9 patterns with real database persistence.

## What's inside

- **25+ entities** per implementation with full business logic (state machines, preconditions, domain events)
- **15 enums** for statuses and types
- **Money as integers** — never floating point, always cents/grosze
- **UUID v7** primary keys
- **Ledger-based inventory** — every stock change recorded as an immutable transaction
- **Shipment lifecycle** — from warehouse picking through carrier delivery with event tracking

These aren't toy examples — they're production patterns you can copy into your codebase and adapt.

## License

MIT License. Pattern concepts based on "Enterprise Patterns and MDA" by Jim Arlow & Ila Neustadt (Addison-Wesley, 2003).

---

### Author

Built by **Jacek Różański**

**[opcode.pl](https://opcode.pl)** — IT, programming and consultancy services. Over 20 years of experience building enterprise systems, APIs, and scalable architectures.

**[theaimechanic.dev](https://theaimechanic.dev)** — Make your AI-generated MVP production-ready. I take prototypes built with AI tools and turn them into maintainable, scalable, properly architected software.
