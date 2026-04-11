<?php

namespace App\Command;

use App\Embeddable\Money;
use App\Entity\Commerce\Order;
use App\Entity\Commerce\Payment;
use App\Entity\Fulfillment\Carrier;
use App\Entity\Fulfillment\InventoryItem;
use App\Entity\Fulfillment\Location;
use App\Entity\Fulfillment\Shipment;
use App\Entity\Party\Address;
use App\Entity\Party\Customer;
use App\Entity\Party\Employment;
use App\Entity\Party\IdentifierType;
use App\Entity\Party\Organization;
use App\Entity\Party\Ownership;
use App\Entity\Party\PartyIdentifier;
use App\Entity\Party\Person;
use App\Entity\Product\AttributeDefinition;
use App\Entity\Product\Product;
use App\Entity\Product\ProductType;
use App\Enum\AddressType;
use App\Enum\DataType;
use App\Enum\EmploymentType;
use App\Enum\Gender;
use App\Enum\LocationType;
use App\Enum\LoyaltyTier;
use App\Enum\PaymentMethod;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo', description: 'Run the full enterprise patterns demo')]
class DemoCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Enterprise Patterns Demo');
        $io->text('Walking through all 9 patterns in an end-to-end e-commerce scenario.');
        $io->newLine();

        // =========================================================
        // 1. PARTY PATTERN — People and Organizations
        // =========================================================
        $io->section('1. Party Pattern — People and Organizations');

        $jan = new Person('Jan', 'Kowalski');
        $jan->setMiddleName('Adam');
        $jan->setBirthDate(new \DateTimeImmutable('1985-03-15'));
        $jan->setGender(Gender::Male);

        $homeAddress = new Address('ul. Marszalkowska 10/5', 'Warszawa', '00-001', 'PL', AddressType::Home, true);
        $jan->addAddress($homeAddress);

        $this->em->persist($jan);

        $io->text("  Created Person: <info>{$jan->getName()}</info>");
        $io->text("  Age: {$jan->getAge()}, Adult: " . ($jan->isAdult() ? 'yes' : 'no'));
        $io->text("  Address: {$homeAddress->getStreet()}, {$homeAddress->getCity()}");

        $acme = new Organization('Acme Sp. z o.o.');
        $acme->setTradingName('Acme');
        $acme->setRegistrationNumber('0000123456');
        $acme->setTaxId('5261234567');

        $acmeAddress = new Address('ul. Polna 22', 'Krakow', '30-001', 'PL', AddressType::Work, true);
        $acme->addAddress($acmeAddress);

        $this->em->persist($acme);

        $io->text("  Created Organization: <info>{$acme->getName()}</info> (official: {$acme->getOfficialName()})");

        $holding = new Organization('Holding S.A.');
        $this->em->persist($holding);

        $io->newLine();

        // =========================================================
        // 2. PARTY IDENTIFIER PATTERN — External IDs
        // =========================================================
        $io->section('2. Party Identifier Pattern — External IDs');

        $peselType = new IdentifierType('PESEL', 'Polish National ID', true, false, '^\d{11}$');
        $nipType = new IdentifierType('NIP', 'Polish Tax ID', false, true, '^\d{10}$');
        $passportType = new IdentifierType('PASSPORT', 'Passport Number', true, false);

        $this->em->persist($peselType);
        $this->em->persist($nipType);
        $this->em->persist($passportType);

        $pesel = new PartyIdentifier($jan, $peselType, '85031512345');
        $pesel->setIssuingAuthority('Urzad Stanu Cywilnego Warszawa');
        $jan->addIdentifier($pesel);

        $pesel->verify('System:PESEL-API');
        $io->text("  Jan's PESEL: <info>{$pesel->getValue()}</info> (verified: " . ($pesel->isVerified() ? 'yes' : 'no') . ')');

        $nip = new PartyIdentifier($acme, $nipType, '5261234567');
        $acme->addIdentifier($nip);
        $io->text("  Acme's NIP: <info>{$nip->getValue()}</info>");

        $io->newLine();

        // =========================================================
        // 3. PARTY ROLE PATTERN — Customer, Employee
        // =========================================================
        $io->section('3. Party Role Pattern — Customer and Employee');

        $customerRole = new Customer($jan, new \DateTimeImmutable('2020-01-15'), 'CUST-00001234');
        $customerRole->setLoyaltyTier(LoyaltyTier::Gold);
        $customerRole->setCreditLimit(new Money(1000000, 'PLN'));

        $this->em->persist($customerRole);

        $io->text("  Jan as Customer: <info>{$customerRole->getCustomerNumber()}</info>");
        $io->text("  Loyalty tier: {$customerRole->getLoyaltyTier()->value}, Credit limit: {$customerRole->getCreditLimit()->format()}");
        $io->text("  Active: " . ($customerRole->isActive() ? 'yes' : 'no'));

        $io->newLine();

        // =========================================================
        // 4. PARTY RELATIONSHIP PATTERN — Employment, Ownership
        // =========================================================
        $io->section('4. Party Relationship Pattern — Employment and Ownership');

        $employment = new Employment(
            $jan,
            $acme,
            new \DateTimeImmutable('2022-06-01'),
            'Senior Developer',
            EmploymentType::FullTime,
        );
        $employment->setDepartment('Engineering');
        $employment->setWorkLocation('Krakow Office');

        $this->em->persist($employment);

        $io->text("  Employment: <info>{$jan->getName()}</info> works for <info>{$acme->getName()}</info>");
        $io->text("  Title: {$employment->getJobTitle()}, Type: {$employment->getEmploymentType()->value}");
        $io->text("  Active: " . ($employment->isActive() ? 'yes' : 'no'));

        $employment->promote('Tech Lead', 'Engineering');
        $io->text("  Promoted to: <info>{$employment->getJobTitle()}</info>");

        $ownership = new Ownership($holding, $acme, new \DateTimeImmutable('2018-01-01'), 75.0);
        $this->em->persist($ownership);
        $io->text("  Ownership: {$holding->getName()} owns {$ownership->getOwnershipPercentage()}% of {$acme->getName()} (majority: " . ($ownership->isMajorityOwner() ? 'yes' : 'no') . ')');

        $io->newLine();

        // =========================================================
        // 5. PRODUCT ARCHETYPE — Catalog
        // =========================================================
        $io->section('5. Product Archetype — Catalog Setup');

        $electronics = new ProductType('ELECTRONICS', 'Electronics');
        $laptops = new ProductType('LAPTOP', 'Laptops', $electronics);
        $peripherals = new ProductType('PERIPHERAL', 'Peripherals', $electronics);

        $ramAttr = new AttributeDefinition('RAM_GB', 'RAM', DataType::Integer, true);
        $ramAttr->setUnit('GB');
        $ramAttr->setRange(4, 128);
        $laptops->addAttributeDefinition($ramAttr);

        $storageAttr = new AttributeDefinition('STORAGE_GB', 'Storage', DataType::Integer, true);
        $storageAttr->setUnit('GB');
        $laptops->addAttributeDefinition($storageAttr);

        $this->em->persist($electronics);
        $this->em->persist($laptops);
        $this->em->persist($peripherals);

        $thinkpad = new Product('LEN-X1C-G11-16-512', 'ThinkPad X1 Carbon Gen 11 (16GB/512GB)', $laptops, new Money(649900, 'PLN'));
        $thinkpad->publish();
        $this->em->persist($thinkpad);

        $mouse = new Product('LOG-MX-MASTER-3S', 'Logitech MX Master 3S', $peripherals, new Money(44900, 'PLN'));
        $mouse->publish();
        $this->em->persist($mouse);

        $io->text("  ProductType path: " . implode(' > ', $laptops->getPath()));
        $io->text("  Product: <info>{$thinkpad->getName()}</info> — {$thinkpad->getBasePrice()->format()} (status: {$thinkpad->getStatus()->value})");
        $io->text("  Product: <info>{$mouse->getName()}</info> — {$mouse->getBasePrice()->format()} (status: {$mouse->getStatus()->value})");

        $io->newLine();

        // =========================================================
        // 6. INVENTORY PATTERN — Stock Setup
        // =========================================================
        $io->section('6. Inventory Pattern — Receive Stock');

        $warehouse = new Location('WH-01', 'Warehouse 1', LocationType::Warehouse);
        $zone = new Location('WH-01-A', 'Zone A', LocationType::Zone, $warehouse);
        $bin = new Location('WH-01-A-001', 'Bin 001', LocationType::Bin, $zone);

        $this->em->persist($warehouse);
        $this->em->persist($zone);
        $this->em->persist($bin);

        $thinkpadStock = new InventoryItem($thinkpad, $bin);
        $thinkpadStock->receive(20, 'PO-2026-0015', 'Supplier delivery');
        $this->em->persist($thinkpadStock);

        $mouseStock = new InventoryItem($mouse, $bin);
        $mouseStock->receive(50, 'PO-2026-0016', 'Supplier delivery');
        $this->em->persist($mouseStock);

        $io->text("  Location: {$bin->getPath()}");
        $io->text("  {$thinkpad->getSku()}: on_hand={$thinkpadStock->getQuantityOnHand()}, reserved={$thinkpadStock->getQuantityReserved()}, available={$thinkpadStock->getQuantityAvailable()}");
        $io->text("  {$mouse->getSku()}: on_hand={$mouseStock->getQuantityOnHand()}, reserved={$mouseStock->getQuantityReserved()}, available={$mouseStock->getQuantityAvailable()}");

        $io->newLine();

        // =========================================================
        // 7. ORDER ARCHETYPE — Place Order
        // =========================================================
        $io->section('7. Order Archetype — Place and Confirm Order');

        $order = new Order('ORD-2026-000042', $customerRole, 'PLN', $homeAddress->toArray());
        $order->setShippingAddress($homeAddress->toArray());

        $line1 = $order->addLine($thinkpad, 1, $thinkpad->getBasePrice());
        $line2 = $order->addLine($mouse, 2, $mouse->getBasePrice());

        $this->em->persist($order);

        $io->text("  Order: <info>{$order->getOrderNumber()}</info> — Status: {$order->getStatus()->value}");
        $io->text("  Line 1: {$line1->getProductName()} x{$line1->getQuantity()} = {$line1->getLineTotal()->format()}");
        $io->text("  Line 2: {$line2->getProductName()} x{$line2->getQuantity()} = {$line2->getLineTotal()->format()}");
        $io->text("  Subtotal: {$order->getSubtotal()->format()}, Tax: {$order->getTaxTotal()->format()}, Grand Total: <info>{$order->getGrandTotal()->format()}</info>");

        $order->confirm();
        $io->text("  Status after confirm: <info>{$order->getStatus()->value}</info>");

        $io->newLine();

        // =========================================================
        // 8. INVENTORY — Reserve Stock
        // =========================================================
        $io->section('8. Inventory — Reserve Stock for Order');

        $thinkpadStock->reserve(1, $order->getOrderNumber(), 'Order reservation');
        $mouseStock->reserve(2, $order->getOrderNumber(), 'Order reservation');

        $io->text("  {$thinkpad->getSku()}: on_hand={$thinkpadStock->getQuantityOnHand()}, reserved={$thinkpadStock->getQuantityReserved()}, available={$thinkpadStock->getQuantityAvailable()}");
        $io->text("  {$mouse->getSku()}: on_hand={$mouseStock->getQuantityOnHand()}, reserved={$mouseStock->getQuantityReserved()}, available={$mouseStock->getQuantityAvailable()}");

        $io->newLine();

        // =========================================================
        // 9. PAYMENT PATTERN — Pay for Order
        // =========================================================
        $io->section('9. Payment Pattern — Process Payment');

        $payment = new Payment('PAY-2026-000089', $order, $customerRole, $order->getGrandTotal(), PaymentMethod::Card);
        $this->em->persist($payment);

        $io->text("  Payment: <info>{$payment->getReference()}</info> — {$payment->getAmount()->format()} ({$payment->getMethod()->value})");
        $io->text("  Status: {$payment->getStatus()->value}");

        $payment->markProcessing();
        $io->text("  -> Processing...");

        $payment->complete('gw_txn_abc123xyz');
        $payment->setCardInfo('4242', 'Visa');
        $io->text("  -> Completed (gateway: gw_txn_abc123xyz, card: ****4242 Visa)");

        $order->markPaid($payment);
        $io->text("  Order status: <info>{$order->getStatus()->value}</info>");

        // Partial refund
        $io->newLine();
        $io->text('  --- Partial Refund ---');
        $refund = Payment::createRefund($payment, new Money(44900, 'PLN'), 'REF-2026-000012');
        $this->em->persist($refund);
        $refund->complete('gw_ref_def456');

        $io->text("  Refund: {$refund->getFormattedAmount()} — Status: {$refund->getStatus()->value}");
        $io->text("  Original payment status: {$payment->getStatus()->value}");
        $io->text("  Refundable remaining: {$payment->getRefundableAmount()->format()}");

        $io->newLine();

        // =========================================================
        // 10. SHIPMENT PATTERN — Ship the Order
        // =========================================================
        $io->section('10. Shipment Pattern — Fulfillment');

        $order->startProcessing();

        $dhl = new Carrier('DHL', 'DHL Express', 'https://www.dhl.com/track?num={tracking}');
        $this->em->persist($dhl);

        $shipment = Shipment::fromOrder($order, 'SHP-2026-000018', $acmeAddress->toArray());
        $shipment->addItemFromOrderLine($line1, 1);
        $shipment->addItemFromOrderLine($line2, 2);

        $this->em->persist($shipment);

        $io->text("  Shipment: <info>{$shipment->getShipmentNumber()}</info>");
        $io->text("  Items: {$shipment->getItems()->count()}");

        // Warehouse flow
        $shipment->startPicking();
        $io->text("  -> {$shipment->getStatus()->value}");

        $shipment->markPacked();
        $io->text("  -> {$shipment->getStatus()->value}");

        $shipment->assignCarrier($dhl, '1234567890');
        $shipment->markLabelPrinted();
        $io->text("  -> {$shipment->getStatus()->value} (carrier: DHL, tracking: 1234567890)");

        $shipment->handOverToCarrier();
        $io->text("  -> {$shipment->getStatus()->value}");
        $io->text("  Tracking URL: {$shipment->getTrackingUrl()}");

        $order->markShipped();

        // Issue stock
        $io->newLine();
        $io->text('  --- Issue Stock (inventory) ---');
        $thinkpadStock->shipReserved(1, $order->getOrderNumber());
        $mouseStock->shipReserved(2, $order->getOrderNumber());

        $io->text("  {$thinkpad->getSku()}: on_hand={$thinkpadStock->getQuantityOnHand()}, reserved={$thinkpadStock->getQuantityReserved()}, available={$thinkpadStock->getQuantityAvailable()}");
        $io->text("  {$mouse->getSku()}: on_hand={$mouseStock->getQuantityOnHand()}, reserved={$mouseStock->getQuantityReserved()}, available={$mouseStock->getQuantityAvailable()}");

        // Carrier delivery
        $io->newLine();
        $io->text('  --- Carrier Delivery ---');
        $shipment->markInTransit('Warszawa Hub');
        $io->text("  -> {$shipment->getStatus()->value}");

        $shipment->markOutForDelivery();
        $io->text("  -> {$shipment->getStatus()->value}");

        $shipment->markDelivered(['signature' => 'J. Kowalski', 'photo' => 'proof_123.jpg']);
        $io->text("  -> {$shipment->getStatus()->value}");

        $order->markDelivered();
        $order->complete();
        $io->text("  Order final status: <info>{$order->getStatus()->value}</info>");

        $io->newLine();

        // =========================================================
        // FLUSH — Persist everything to database
        // =========================================================
        $this->em->flush();

        // =========================================================
        // SUMMARY
        // =========================================================
        $io->section('Summary');

        $io->table(
            ['Pattern', 'Entities Created'],
            [
                ['Party', "Person ({$jan->getName()}), Organization ({$acme->getName()}, {$holding->getName()})"],
                ['Party Identifier', "PESEL ({$pesel->getValue()}), NIP ({$nip->getValue()})"],
                ['Party Role', "Customer ({$customerRole->getCustomerNumber()})"],
                ['Party Relationship', "Employment ({$employment->getJobTitle()}), Ownership ({$ownership->getOwnershipPercentage()}%)"],
                ['Product', "ThinkPad ({$thinkpad->getSku()}), Mouse ({$mouse->getSku()})"],
                ['Order', "{$order->getOrderNumber()} — {$order->getGrandTotal()->format()} ({$order->getStatus()->value})"],
                ['Payment', "{$payment->getReference()} — {$payment->getAmount()->format()} ({$payment->getStatus()->value})"],
                ['Inventory', "ThinkPad: {$thinkpadStock->getQuantityOnHand()} on hand, Mouse: {$mouseStock->getQuantityOnHand()} on hand"],
                ['Shipment', "{$shipment->getShipmentNumber()} — {$shipment->getStatus()->value} ({$shipment->getEvents()->count()} events)"],
            ]
        );

        $txCount = $thinkpadStock->getTransactions()->count() + $mouseStock->getTransactions()->count();
        $io->success("Demo complete! All 9 patterns exercised. {$txCount} inventory transactions recorded.");

        return Command::SUCCESS;
    }
}
