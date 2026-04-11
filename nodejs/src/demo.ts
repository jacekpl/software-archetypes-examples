import { DataSource } from "typeorm";
import { Money } from "./embeddable/Money";
import {
  AddressType,
  DataType,
  EmploymentType,
  Gender,
  LocationType,
  LoyaltyTier,
  PaymentMethod,
} from "./enum";

// Party entities
import { Person } from "./entity/party/Person";
import { Organization } from "./entity/party/Organization";
import { Address } from "./entity/party/Address";
import { IdentifierType } from "./entity/party/IdentifierType";
import { PartyIdentifier } from "./entity/party/PartyIdentifier";
import { Customer } from "./entity/party/Customer";
import { Employment } from "./entity/party/Employment";
import { Ownership } from "./entity/party/Ownership";

// Product entities
import { ProductType } from "./entity/product/ProductType";
import { AttributeDefinition } from "./entity/product/AttributeDefinition";
import { Product } from "./entity/product/Product";

// Commerce entities
import { Order } from "./entity/commerce/Order";
import { Payment } from "./entity/commerce/Payment";

// Fulfillment entities
import { Location } from "./entity/fulfillment/Location";
import { InventoryItem } from "./entity/fulfillment/InventoryItem";
import { Carrier } from "./entity/fulfillment/Carrier";
import { Shipment } from "./entity/fulfillment/Shipment";

// ANSI color helpers
const CYAN = "\x1b[1;36m";
const GREEN = "\x1b[1;32m";
const RESET = "\x1b[0m";

function section(number: number, title: string): void {
  console.log(`\n${CYAN}${"=".repeat(60)}`);
  console.log(`  ${number}. ${title}`);
  console.log(`${"=".repeat(60)}${RESET}\n`);
}

function info(text: string): void {
  console.log(`  ${text}`);
}

export async function runDemo(dataSource: DataSource): Promise<void> {
  console.log(`${CYAN}Enterprise Patterns Demo${RESET}`);
  console.log("Walking through all 9 patterns in an end-to-end e-commerce scenario.");

  await dataSource.manager.transaction(async (manager) => {
    // =========================================================
    // 1. PARTY PATTERN -- People and Organizations
    // =========================================================
    section(1, "Party Pattern -- People and Organizations");

    const jan = new Person("Jan", "Kowalski");
    jan.middleName = "Adam";
    jan.birthDate = "1985-03-15";
    jan.gender = Gender.Male;
    await manager.save(jan);

    const homeAddress = new Address("ul. Marszalkowska 10/5", "Warszawa", "00-001", "PL", AddressType.Home, true);
    jan.addAddress(homeAddress);
    await manager.save(homeAddress);

    info(`Created Person: ${GREEN}${jan.getName()}${RESET}`);
    info(`Age: ${jan.getAge()}, Adult: ${jan.isAdult() ? "yes" : "no"}`);
    info(`Address: ${homeAddress.street}, ${homeAddress.city}`);

    const acme = new Organization("Acme Sp. z o.o.");
    acme.tradingName = "Acme";
    acme.registrationNumber = "0000123456";
    acme.taxId = "5261234567";

    await manager.save(acme);

    const acmeAddress = new Address("ul. Polna 22", "Krakow", "30-001", "PL", AddressType.Work, true);
    acme.addAddress(acmeAddress);
    await manager.save(acmeAddress);

    info(`Created Organization: ${GREEN}${acme.getName()}${RESET} (official: ${acme.getOfficialName()})`);

    const holding = new Organization("Holding S.A.");
    await manager.save(holding);

    // =========================================================
    // 2. PARTY IDENTIFIER PATTERN -- External IDs
    // =========================================================
    section(2, "Party Identifier Pattern -- External IDs");

    const peselType = new IdentifierType("PESEL", "Polish National ID", true, false, "^\\d{11}$");
    const nipType = new IdentifierType("NIP", "Polish Tax ID", false, true, "^\\d{10}$");
    const passportType = new IdentifierType("PASSPORT", "Passport Number", true, false);

    await manager.save([peselType, nipType, passportType]);

    const pesel = new PartyIdentifier(jan, peselType, "85031512345");
    pesel.issuingAuthority = "Urzad Stanu Cywilnego Warszawa";
    jan.addIdentifier(pesel);

    pesel.verify("System:PESEL-API");
    info(`Jan's PESEL: ${GREEN}${pesel.value}${RESET} (verified: ${pesel.isVerified() ? "yes" : "no"})`);

    const nip = new PartyIdentifier(acme, nipType, "5261234567");
    acme.addIdentifier(nip);
    info(`Acme's NIP: ${GREEN}${nip.value}${RESET}`);

    await manager.save([jan, acme]);

    // =========================================================
    // 3. PARTY ROLE PATTERN -- Customer
    // =========================================================
    section(3, "Party Role Pattern -- Customer and Employee");

    const customerRole = new Customer(jan, "2020-01-15", "CUST-00001234");
    customerRole.loyaltyTier = LoyaltyTier.Gold;
    customerRole.setCreditLimit(1000000, "PLN");

    await manager.save(customerRole);

    info(`Jan as Customer: ${GREEN}${customerRole.customerNumber}${RESET}`);
    info(`Loyalty tier: ${customerRole.loyaltyTier}, Credit limit: ${customerRole.getCreditLimitFormatted()}`);
    info(`Active: ${customerRole.isActive() ? "yes" : "no"}`);

    // =========================================================
    // 4. PARTY RELATIONSHIP PATTERN -- Employment, Ownership
    // =========================================================
    section(4, "Party Relationship Pattern -- Employment and Ownership");

    const employment = new Employment(
      jan,
      acme,
      "2022-06-01",
      "Senior Developer",
      EmploymentType.FullTime,
    );
    employment.department = "Engineering";
    employment.workLocation = "Krakow Office";

    await manager.save(employment);

    info(`Employment: ${GREEN}${jan.getName()}${RESET} works for ${GREEN}${acme.getName()}${RESET}`);
    info(`Title: ${employment.jobTitle}, Type: ${employment.employmentType}`);
    info(`Active: ${employment.isActive() ? "yes" : "no"}`);

    employment.promote("Tech Lead", "Engineering");
    info(`Promoted to: ${GREEN}${employment.jobTitle}${RESET}`);

    const ownership = new Ownership(holding, acme, "2018-01-01", 75.0);
    await manager.save(ownership);
    info(`Ownership: ${holding.getName()} owns ${ownership.ownershipPercentage}% of ${acme.getName()} (majority: ${ownership.isMajorityOwner() ? "yes" : "no"})`);

    // =========================================================
    // 5. PRODUCT ARCHETYPE -- Catalog
    // =========================================================
    section(5, "Product Archetype -- Catalog Setup");

    const electronics = new ProductType("ELECTRONICS", "Electronics");
    const laptops = new ProductType("LAPTOP", "Laptops", electronics);
    const peripherals = new ProductType("PERIPHERAL", "Peripherals", electronics);

    const ramAttr = new AttributeDefinition("RAM_GB", "RAM", DataType.Integer, true);
    ramAttr.unit = "GB";
    ramAttr.setRange(4, 128);
    laptops.addAttributeDefinition(ramAttr);

    const storageAttr = new AttributeDefinition("STORAGE_GB", "Storage", DataType.Integer, true);
    storageAttr.unit = "GB";
    laptops.addAttributeDefinition(storageAttr);

    await manager.save([electronics, laptops, peripherals]);

    const thinkpad = new Product("LEN-X1C-G11-16-512", "ThinkPad X1 Carbon Gen 11 (16GB/512GB)", laptops, new Money(649900, "PLN"));
    thinkpad.publish();
    await manager.save(thinkpad);

    const mouse = new Product("LOG-MX-MASTER-3S", "Logitech MX Master 3S", peripherals, new Money(44900, "PLN"));
    mouse.publish();
    await manager.save(mouse);

    info(`ProductType path: ${laptops.getPath().join(" > ")}`);
    info(`Product: ${GREEN}${thinkpad.name}${RESET} -- ${thinkpad.getBasePrice().format()} (status: ${thinkpad.status})`);
    info(`Product: ${GREEN}${mouse.name}${RESET} -- ${mouse.getBasePrice().format()} (status: ${mouse.status})`);

    // =========================================================
    // 6. INVENTORY PATTERN -- Stock Setup
    // =========================================================
    section(6, "Inventory Pattern -- Receive Stock");

    const warehouse = new Location("WH-01", "Warehouse 1", LocationType.Warehouse);
    const zone = new Location("WH-01-A", "Zone A", LocationType.Zone, warehouse);
    const bin = new Location("WH-01-A-001", "Bin 001", LocationType.Bin, zone);

    await manager.save([warehouse, zone, bin]);

    const thinkpadStock = new InventoryItem(thinkpad, bin);
    thinkpadStock.receive(20, "PO-2026-0015", "Supplier delivery");
    await manager.save(thinkpadStock);

    const mouseStock = new InventoryItem(mouse, bin);
    mouseStock.receive(50, "PO-2026-0016", "Supplier delivery");
    await manager.save(mouseStock);

    info(`Location: ${bin.getPath()}`);
    info(`${thinkpad.sku}: on_hand=${thinkpadStock.quantityOnHand}, reserved=${thinkpadStock.quantityReserved}, available=${thinkpadStock.getQuantityAvailable()}`);
    info(`${mouse.sku}: on_hand=${mouseStock.quantityOnHand}, reserved=${mouseStock.quantityReserved}, available=${mouseStock.getQuantityAvailable()}`);

    // =========================================================
    // 7. ORDER ARCHETYPE -- Place Order
    // =========================================================
    section(7, "Order Archetype -- Place and Confirm Order");

    const order = new Order("ORD-2026-000042", customerRole, "PLN", homeAddress.toObject());
    order.shippingAddress = homeAddress.toObject();

    const line1 = order.addLine(thinkpad, 1, thinkpad.getBasePrice());
    const line2 = order.addLine(mouse, 2, mouse.getBasePrice());

    await manager.save(order);

    info(`Order: ${GREEN}${order.orderNumber}${RESET} -- Status: ${order.status}`);
    info(`Line 1: ${line1.productName} x${line1.quantity} = ${line1.getLineTotal().format()}`);
    info(`Line 2: ${line2.productName} x${line2.quantity} = ${line2.getLineTotal().format()}`);
    info(`Subtotal: ${order.getSubtotal().format()}, Tax: ${order.getTaxTotal().format()}, Grand Total: ${GREEN}${order.getGrandTotal().format()}${RESET}`);

    order.confirm();
    info(`Status after confirm: ${GREEN}${order.status}${RESET}`);

    // =========================================================
    // 8. INVENTORY -- Reserve Stock
    // =========================================================
    section(8, "Inventory -- Reserve Stock for Order");

    thinkpadStock.reserve(1, order.orderNumber, "Order reservation");
    mouseStock.reserve(2, order.orderNumber, "Order reservation");

    info(`${thinkpad.sku}: on_hand=${thinkpadStock.quantityOnHand}, reserved=${thinkpadStock.quantityReserved}, available=${thinkpadStock.getQuantityAvailable()}`);
    info(`${mouse.sku}: on_hand=${mouseStock.quantityOnHand}, reserved=${mouseStock.quantityReserved}, available=${mouseStock.getQuantityAvailable()}`);

    // =========================================================
    // 9. PAYMENT PATTERN -- Pay for Order
    // =========================================================
    section(9, "Payment Pattern -- Process Payment");

    const payment = new Payment("PAY-2026-000089", order, customerRole, order.getGrandTotal(), PaymentMethod.Card);
    await manager.save(payment);

    info(`Payment: ${GREEN}${payment.reference}${RESET} -- ${payment.getAmount().format()} (${payment.method})`);
    info(`Status: ${payment.status}`);

    payment.markProcessing();
    info("-> Processing...");

    payment.complete("gw_txn_abc123xyz");
    payment.setCardInfo("4242", "Visa");
    info("-> Completed (gateway: gw_txn_abc123xyz, card: ****4242 Visa)");

    order.markPaid(payment);
    info(`Order status: ${GREEN}${order.status}${RESET}`);

    // Partial refund
    console.log("");
    info("--- Partial Refund ---");
    const refund = Payment.createRefund(payment, new Money(44900, "PLN"), "REF-2026-000012");
    await manager.save(refund);
    refund.complete("gw_ref_def456");

    info(`Refund: ${refund.getFormattedAmount()} -- Status: ${refund.status}`);
    info(`Original payment status: ${payment.status}`);
    info(`Refundable remaining: ${payment.getRefundableAmount().format()}`);

    // =========================================================
    // 10. SHIPMENT PATTERN -- Ship the Order
    // =========================================================
    section(10, "Shipment Pattern -- Fulfillment");

    order.startProcessing();

    const dhl = new Carrier("DHL", "DHL Express", "https://www.dhl.com/track?num={tracking}");
    await manager.save(dhl);

    const shipment = Shipment.fromOrder(order, "SHP-2026-000018", acmeAddress.toObject());
    shipment.addItemFromOrderLine(line1, 1);
    shipment.addItemFromOrderLine(line2, 2);

    await manager.save(shipment);

    info(`Shipment: ${GREEN}${shipment.shipmentNumber}${RESET}`);
    info(`Items: ${shipment.items.length}`);

    // Warehouse flow
    shipment.startPicking();
    info(`-> ${shipment.status}`);

    shipment.markPacked();
    info(`-> ${shipment.status}`);

    shipment.assignCarrier(dhl, "1234567890");
    shipment.markLabelPrinted();
    info(`-> ${shipment.status} (carrier: DHL, tracking: 1234567890)`);

    shipment.handOverToCarrier();
    info(`-> ${shipment.status}`);
    info(`Tracking URL: ${shipment.getTrackingUrl()}`);

    order.markShipped();

    // Issue stock
    console.log("");
    info("--- Issue Stock (inventory) ---");
    thinkpadStock.shipReserved(1, order.orderNumber);
    mouseStock.shipReserved(2, order.orderNumber);

    info(`${thinkpad.sku}: on_hand=${thinkpadStock.quantityOnHand}, reserved=${thinkpadStock.quantityReserved}, available=${thinkpadStock.getQuantityAvailable()}`);
    info(`${mouse.sku}: on_hand=${mouseStock.quantityOnHand}, reserved=${mouseStock.quantityReserved}, available=${mouseStock.getQuantityAvailable()}`);

    // Carrier delivery
    console.log("");
    info("--- Carrier Delivery ---");
    shipment.markInTransit("Warszawa Hub");
    info(`-> ${shipment.status}`);

    shipment.markOutForDelivery();
    info(`-> ${shipment.status}`);

    shipment.markDelivered({ signature: "J. Kowalski", photo: "proof_123.jpg" });
    info(`-> ${shipment.status}`);

    order.markDelivered();
    order.complete();
    info(`Order final status: ${GREEN}${order.status}${RESET}`);

    // Save all final state
    await manager.save([order, payment, refund, shipment, thinkpadStock, mouseStock, employment]);

    // =========================================================
    // SUMMARY
    // =========================================================
    section(11, "Summary");

    const rows = [
      ["Party", `Person (${jan.getName()}), Organization (${acme.getName()}, ${holding.getName()})`],
      ["Party Identifier", `PESEL (${pesel.value}), NIP (${nip.value})`],
      ["Party Role", `Customer (${customerRole.customerNumber})`],
      ["Party Relationship", `Employment (${employment.jobTitle}), Ownership (${ownership.ownershipPercentage}%)`],
      ["Product", `ThinkPad (${thinkpad.sku}), Mouse (${mouse.sku})`],
      ["Order", `${order.orderNumber} -- ${order.getGrandTotal().format()} (${order.status})`],
      ["Payment", `${payment.reference} -- ${payment.getAmount().format()} (${payment.status})`],
      ["Inventory", `ThinkPad: ${thinkpadStock.quantityOnHand} on hand, Mouse: ${mouseStock.quantityOnHand} on hand`],
      ["Shipment", `${shipment.shipmentNumber} -- ${shipment.status} (${shipment.events.length} events)`],
    ];

    // Print table
    const col1Width = 20;
    const col2Width = 70;
    const separator = `+-${"-".repeat(col1Width)}-+-${"-".repeat(col2Width)}-+`;

    console.log(separator);
    console.log(`| ${"Pattern".padEnd(col1Width)} | ${"Entities Created".padEnd(col2Width)} |`);
    console.log(separator);
    for (const [pattern, entities] of rows) {
      console.log(`| ${pattern.padEnd(col1Width)} | ${entities.padEnd(col2Width)} |`);
    }
    console.log(separator);

    const txCount = thinkpadStock.transactions.length + mouseStock.transactions.length;
    console.log(`\n${GREEN}Demo complete! All 9 patterns exercised. ${txCount} inventory transactions recorded.${RESET}\n`);
  });
}
