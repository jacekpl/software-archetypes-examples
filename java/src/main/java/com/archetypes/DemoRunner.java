package com.archetypes;

import com.archetypes.embeddable.Money;
import com.archetypes.entity.commerce.Order;
import com.archetypes.entity.commerce.OrderLine;
import com.archetypes.entity.commerce.Payment;
import com.archetypes.entity.fulfillment.*;
import com.archetypes.entity.party.*;
import com.archetypes.entity.product.AttributeDefinition;
import com.archetypes.entity.product.Product;
import com.archetypes.entity.product.ProductType;
import com.archetypes.enums.*;
import jakarta.persistence.EntityManager;
import jakarta.persistence.PersistenceContext;
import org.springframework.boot.CommandLineRunner;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.Map;

@Component
public class DemoRunner implements CommandLineRunner {

    private static final String BOLD_CYAN = "\033[1;36m";
    private static final String BOLD_GREEN = "\033[1;32m";
    private static final String RESET = "\033[0m";

    @PersistenceContext
    private EntityManager em;

    @Override
    @Transactional
    public void run(String... args) {
        println("");
        println(BOLD_CYAN + "========================================" + RESET);
        println(BOLD_CYAN + "   Enterprise Patterns Demo" + RESET);
        println(BOLD_CYAN + "========================================" + RESET);
        println("Walking through all 9 patterns in an end-to-end e-commerce scenario.");
        println("");

        // =========================================================
        // 1. PARTY PATTERN -- People and Organizations
        // =========================================================
        section("1. Party Pattern -- People and Organizations");

        Person jan = new Person("Jan", "Kowalski");
        jan.setMiddleName("Adam");
        jan.setBirthDate(LocalDate.of(1985, 3, 15));
        jan.setGender(Gender.Male);

        Address homeAddress = new Address("ul. Marszalkowska 10/5", "Warszawa", "00-001", "PL", AddressType.Home, true);
        jan.addAddress(homeAddress);

        em.persist(jan);

        info("Created Person", jan.getName());
        println("  Age: " + jan.getAge() + ", Adult: " + (jan.isAdult() ? "yes" : "no"));
        println("  Address: " + homeAddress.getStreet() + ", " + homeAddress.getCity());

        Organization acme = new Organization("Acme Sp. z o.o.");
        acme.setTradingName("Acme");
        acme.setRegistrationNumber("0000123456");
        acme.setTaxId("5261234567");

        Address acmeAddress = new Address("ul. Polna 22", "Krakow", "30-001", "PL", AddressType.Work, true);
        acme.addAddress(acmeAddress);

        em.persist(acme);

        info("Created Organization", acme.getName() + " (official: " + acme.getOfficialName() + ")");

        Organization holding = new Organization("Holding S.A.");
        em.persist(holding);

        println("");

        // =========================================================
        // 2. PARTY IDENTIFIER PATTERN -- External IDs
        // =========================================================
        section("2. Party Identifier Pattern -- External IDs");

        IdentifierType peselType = new IdentifierType("PESEL", "Polish National ID", true, false, "^\\d{11}$");
        IdentifierType nipType = new IdentifierType("NIP", "Polish Tax ID", false, true, "^\\d{10}$");
        IdentifierType passportType = new IdentifierType("PASSPORT", "Passport Number", true, false);

        em.persist(peselType);
        em.persist(nipType);
        em.persist(passportType);

        PartyIdentifier pesel = new PartyIdentifier(jan, peselType, "85031512345");
        pesel.setIssuingAuthority("Urzad Stanu Cywilnego Warszawa");
        jan.addIdentifier(pesel);

        pesel.verify("System:PESEL-API");
        info("Jan's PESEL", pesel.getValue() + " (verified: " + (pesel.isVerified() ? "yes" : "no") + ")");

        PartyIdentifier nip = new PartyIdentifier(acme, nipType, "5261234567");
        acme.addIdentifier(nip);
        info("Acme's NIP", nip.getValue());

        println("");

        // =========================================================
        // 3. PARTY ROLE PATTERN -- Customer
        // =========================================================
        section("3. Party Role Pattern -- Customer and Employee");

        Customer customerRole = new Customer(jan, LocalDate.of(2020, 1, 15), "CUST-00001234");
        customerRole.setLoyaltyTier(LoyaltyTier.Gold);
        customerRole.setCreditLimit(new Money(1000000, "PLN"));

        em.persist(customerRole);

        info("Jan as Customer", customerRole.getCustomerNumber());
        println("  Loyalty tier: " + customerRole.getLoyaltyTier().name() + ", Credit limit: " + customerRole.getCreditLimit().format());
        println("  Active: " + (customerRole.isActive() ? "yes" : "no"));

        println("");

        // =========================================================
        // 4. PARTY RELATIONSHIP PATTERN -- Employment, Ownership
        // =========================================================
        section("4. Party Relationship Pattern -- Employment and Ownership");

        Employment employment = new Employment(
                jan, acme,
                LocalDate.of(2022, 6, 1),
                "Senior Developer",
                EmploymentType.FullTime
        );
        employment.setDepartment("Engineering");
        employment.setWorkLocation("Krakow Office");

        em.persist(employment);

        println("  Employment: " + BOLD_GREEN + jan.getName() + RESET + " works for " + BOLD_GREEN + acme.getName() + RESET);
        println("  Title: " + employment.getJobTitle() + ", Type: " + employment.getEmploymentType().name());
        println("  Active: " + (employment.isActive() ? "yes" : "no"));

        employment.promote("Tech Lead", "Engineering");
        info("Promoted to", employment.getJobTitle());

        Ownership ownership = new Ownership(holding, acme, LocalDate.of(2018, 1, 1), 75.0, true);
        em.persist(ownership);
        println("  Ownership: " + holding.getName() + " owns " + ownership.getOwnershipPercentage() + "% of " + acme.getName()
                + " (majority: " + (ownership.isMajorityOwner() ? "yes" : "no") + ")");

        println("");

        // =========================================================
        // 5. PRODUCT ARCHETYPE -- Catalog
        // =========================================================
        section("5. Product Archetype -- Catalog Setup");

        ProductType electronics = new ProductType("ELECTRONICS", "Electronics");
        ProductType laptops = new ProductType("LAPTOP", "Laptops", electronics);
        ProductType peripherals = new ProductType("PERIPHERAL", "Peripherals", electronics);

        AttributeDefinition ramAttr = new AttributeDefinition("RAM_GB", "RAM", DataType.Integer, true);
        ramAttr.setUnit("GB");
        ramAttr.setRange(4.0, 128.0);
        laptops.addAttributeDefinition(ramAttr);

        AttributeDefinition storageAttr = new AttributeDefinition("STORAGE_GB", "Storage", DataType.Integer, true);
        storageAttr.setUnit("GB");
        laptops.addAttributeDefinition(storageAttr);

        em.persist(electronics);
        em.persist(laptops);
        em.persist(peripherals);

        Product thinkpad = new Product("LEN-X1C-G11-16-512", "ThinkPad X1 Carbon Gen 11 (16GB/512GB)", laptops, new Money(649900, "PLN"));
        thinkpad.publish();
        em.persist(thinkpad);

        Product mouse = new Product("LOG-MX-MASTER-3S", "Logitech MX Master 3S", peripherals, new Money(44900, "PLN"));
        mouse.publish();
        em.persist(mouse);

        println("  ProductType path: " + String.join(" > ", laptops.getPath()));
        info("Product", thinkpad.getName() + " -- " + thinkpad.getBasePrice().format() + " (status: " + thinkpad.getStatus().name() + ")");
        info("Product", mouse.getName() + " -- " + mouse.getBasePrice().format() + " (status: " + mouse.getStatus().name() + ")");

        println("");

        // =========================================================
        // 6. INVENTORY PATTERN -- Stock Setup
        // =========================================================
        section("6. Inventory Pattern -- Receive Stock");

        Location warehouse = new Location("WH-01", "Warehouse 1", LocationType.Warehouse);
        Location zone = new Location("WH-01-A", "Zone A", LocationType.Zone, warehouse);
        Location bin = new Location("WH-01-A-001", "Bin 001", LocationType.Bin, zone);

        em.persist(warehouse);
        em.persist(zone);
        em.persist(bin);

        InventoryItem thinkpadStock = new InventoryItem(thinkpad, bin);
        thinkpadStock.receive(20, "PO-2026-0015", "Supplier delivery");
        em.persist(thinkpadStock);

        InventoryItem mouseStock = new InventoryItem(mouse, bin);
        mouseStock.receive(50, "PO-2026-0016", "Supplier delivery");
        em.persist(mouseStock);

        println("  Location: " + bin.getPath());
        println("  " + thinkpad.getSku() + ": on_hand=" + thinkpadStock.getQuantityOnHand()
                + ", reserved=" + thinkpadStock.getQuantityReserved()
                + ", available=" + thinkpadStock.getQuantityAvailable());
        println("  " + mouse.getSku() + ": on_hand=" + mouseStock.getQuantityOnHand()
                + ", reserved=" + mouseStock.getQuantityReserved()
                + ", available=" + mouseStock.getQuantityAvailable());

        println("");

        // =========================================================
        // 7. ORDER ARCHETYPE -- Place Order
        // =========================================================
        section("7. Order Archetype -- Place and Confirm Order");

        Order order = new Order("ORD-2026-000042", customerRole, "PLN", homeAddress.toMap());
        order.setShippingAddress(homeAddress.toMap());

        OrderLine line1 = order.addLine(thinkpad, 1, thinkpad.getBasePrice());
        OrderLine line2 = order.addLine(mouse, 2, mouse.getBasePrice());

        em.persist(order);

        info("Order", order.getOrderNumber() + " -- Status: " + order.getStatus().name());
        println("  Line 1: " + line1.getProductName() + " x" + line1.getQuantity() + " = " + line1.getLineTotal().format());
        println("  Line 2: " + line2.getProductName() + " x" + line2.getQuantity() + " = " + line2.getLineTotal().format());
        println("  Subtotal: " + order.getSubtotal().format() + ", Tax: " + order.getTaxTotal().format()
                + ", Grand Total: " + BOLD_GREEN + order.getGrandTotal().format() + RESET);

        order.confirm();
        info("Status after confirm", order.getStatus().name());

        println("");

        // =========================================================
        // 8. INVENTORY -- Reserve Stock
        // =========================================================
        section("8. Inventory -- Reserve Stock for Order");

        thinkpadStock.reserve(1, order.getOrderNumber(), "Order reservation");
        mouseStock.reserve(2, order.getOrderNumber(), "Order reservation");

        println("  " + thinkpad.getSku() + ": on_hand=" + thinkpadStock.getQuantityOnHand()
                + ", reserved=" + thinkpadStock.getQuantityReserved()
                + ", available=" + thinkpadStock.getQuantityAvailable());
        println("  " + mouse.getSku() + ": on_hand=" + mouseStock.getQuantityOnHand()
                + ", reserved=" + mouseStock.getQuantityReserved()
                + ", available=" + mouseStock.getQuantityAvailable());

        println("");

        // =========================================================
        // 9. PAYMENT PATTERN -- Pay for Order
        // =========================================================
        section("9. Payment Pattern -- Process Payment");

        Payment payment = new Payment("PAY-2026-000089", order, customerRole, order.getGrandTotal(), PaymentMethod.Card);
        em.persist(payment);

        info("Payment", payment.getReference() + " -- " + payment.getAmount().format() + " (" + payment.getMethod().name() + ")");
        println("  Status: " + payment.getStatus().name());

        payment.markProcessing();
        println("  -> Processing...");

        payment.complete("gw_txn_abc123xyz");
        payment.setCardInfo("4242", "Visa");
        println("  -> Completed (gateway: gw_txn_abc123xyz, card: ****4242 Visa)");

        order.markPaid(payment);
        info("Order status", order.getStatus().name());

        // Partial refund
        println("");
        println("  --- Partial Refund ---");
        Payment refund = Payment.createRefund(payment, new Money(44900, "PLN"), "REF-2026-000012");
        em.persist(refund);
        refund.complete("gw_ref_def456");

        println("  Refund: " + refund.getFormattedAmount() + " -- Status: " + refund.getStatus().name());
        println("  Original payment status: " + payment.getStatus().name());
        println("  Refundable remaining: " + payment.getRefundableAmount().format());

        println("");

        // =========================================================
        // 10. SHIPMENT PATTERN -- Ship the Order
        // =========================================================
        section("10. Shipment Pattern -- Fulfillment");

        order.startProcessing();

        Carrier dhl = new Carrier("DHL", "DHL Express", "https://www.dhl.com/track?num={tracking}");
        em.persist(dhl);

        Shipment shipment = Shipment.fromOrder(order, "SHP-2026-000018", acmeAddress.toMap());
        shipment.addItemFromOrderLine(line1, 1);
        shipment.addItemFromOrderLine(line2, 2);

        em.persist(shipment);

        info("Shipment", shipment.getShipmentNumber());
        println("  Items: " + shipment.getItems().size());

        // Warehouse flow
        shipment.startPicking();
        println("  -> " + shipment.getStatus().name());

        shipment.markPacked();
        println("  -> " + shipment.getStatus().name());

        shipment.assignCarrier(dhl, "1234567890");
        shipment.markLabelPrinted();
        println("  -> " + shipment.getStatus().name() + " (carrier: DHL, tracking: 1234567890)");

        shipment.handOverToCarrier();
        println("  -> " + shipment.getStatus().name());
        println("  Tracking URL: " + shipment.getTrackingUrl());

        order.markShipped();

        // Issue stock
        println("");
        println("  --- Issue Stock (inventory) ---");
        thinkpadStock.shipReserved(1, order.getOrderNumber());
        mouseStock.shipReserved(2, order.getOrderNumber());

        println("  " + thinkpad.getSku() + ": on_hand=" + thinkpadStock.getQuantityOnHand()
                + ", reserved=" + thinkpadStock.getQuantityReserved()
                + ", available=" + thinkpadStock.getQuantityAvailable());
        println("  " + mouse.getSku() + ": on_hand=" + mouseStock.getQuantityOnHand()
                + ", reserved=" + mouseStock.getQuantityReserved()
                + ", available=" + mouseStock.getQuantityAvailable());

        // Carrier delivery
        println("");
        println("  --- Carrier Delivery ---");
        shipment.markInTransit("Warszawa Hub");
        println("  -> " + shipment.getStatus().name());

        shipment.markOutForDelivery();
        println("  -> " + shipment.getStatus().name());

        shipment.markDelivered(Map.of("signature", "J. Kowalski", "photo", "proof_123.jpg"));
        println("  -> " + shipment.getStatus().name());

        order.markDelivered();
        order.complete();
        info("Order final status", order.getStatus().name());

        println("");

        // =========================================================
        // FLUSH -- Persist everything to database
        // =========================================================
        em.flush();

        // =========================================================
        // SUMMARY
        // =========================================================
        section("Summary");

        String format = "  %-22s %s%n";
        println(BOLD_CYAN + "  Pattern                Entities Created" + RESET);
        println("  " + "-".repeat(60));
        System.out.printf(format, "Party", "Person (" + jan.getName() + "), Organization (" + acme.getName() + ", " + holding.getName() + ")");
        System.out.printf(format, "Party Identifier", "PESEL (" + pesel.getValue() + "), NIP (" + nip.getValue() + ")");
        System.out.printf(format, "Party Role", "Customer (" + customerRole.getCustomerNumber() + ")");
        System.out.printf(format, "Party Relationship", "Employment (" + employment.getJobTitle() + "), Ownership (" + ownership.getOwnershipPercentage() + "%)");
        System.out.printf(format, "Product", "ThinkPad (" + thinkpad.getSku() + "), Mouse (" + mouse.getSku() + ")");
        System.out.printf(format, "Order", order.getOrderNumber() + " -- " + order.getGrandTotal().format() + " (" + order.getStatus().name() + ")");
        System.out.printf(format, "Payment", payment.getReference() + " -- " + payment.getAmount().format() + " (" + payment.getStatus().name() + ")");
        System.out.printf(format, "Inventory", "ThinkPad: " + thinkpadStock.getQuantityOnHand() + " on hand, Mouse: " + mouseStock.getQuantityOnHand() + " on hand");
        System.out.printf(format, "Shipment", shipment.getShipmentNumber() + " -- " + shipment.getStatus().name() + " (" + shipment.getEvents().size() + " events)");

        int txCount = thinkpadStock.getTransactions().size() + mouseStock.getTransactions().size();
        println("");
        println(BOLD_GREEN + "  [OK] Demo complete! All 9 patterns exercised. " + txCount + " inventory transactions recorded." + RESET);
        println("");
    }

    private void section(String title) {
        println(BOLD_CYAN + "--- " + title + " ---" + RESET);
    }

    private void info(String label, String value) {
        println("  " + label + ": " + BOLD_GREEN + value + RESET);
    }

    private void println(String text) {
        System.out.println(text);
    }
}
