import "reflect-metadata";
import { DataSource } from "typeorm";

// Import entities explicitly to control load order and avoid circular dependency issues
import { Party } from "./entity/party/Party";
import { Person } from "./entity/party/Person";
import { Organization } from "./entity/party/Organization";
import { Address } from "./entity/party/Address";
import { PartyRole } from "./entity/party/PartyRole";
import { Customer } from "./entity/party/Customer";
import { Supplier } from "./entity/party/Supplier";
import { Employee } from "./entity/party/Employee";
import { PartyRelationship } from "./entity/party/PartyRelationship";
import { Employment } from "./entity/party/Employment";
import { Ownership } from "./entity/party/Ownership";
import { IdentifierType } from "./entity/party/IdentifierType";
import { PartyIdentifier } from "./entity/party/PartyIdentifier";
import { ProductType } from "./entity/product/ProductType";
import { AttributeDefinition } from "./entity/product/AttributeDefinition";
import { Product } from "./entity/product/Product";
import { ProductInstance } from "./entity/product/ProductInstance";
import { Order } from "./entity/commerce/Order";
import { OrderLine } from "./entity/commerce/OrderLine";
import { Payment } from "./entity/commerce/Payment";
import { Location } from "./entity/fulfillment/Location";
import { InventoryItem } from "./entity/fulfillment/InventoryItem";
import { InventoryTransaction } from "./entity/fulfillment/InventoryTransaction";
import { Carrier } from "./entity/fulfillment/Carrier";
import { Shipment } from "./entity/fulfillment/Shipment";
import { ShipmentItem } from "./entity/fulfillment/ShipmentItem";
import { ShipmentEvent } from "./entity/fulfillment/ShipmentEvent";

export const AppDataSource = new DataSource({
  type: "postgres",
  host: process.env.DB_HOST || "db",
  port: 5432,
  username: "app",
  password: "app",
  database: "archetypes",
  synchronize: true,
  logging: false,
  entities: [
    Party, Person, Organization, Address,
    PartyRole, Customer, Supplier, Employee,
    PartyRelationship, Employment, Ownership,
    IdentifierType, PartyIdentifier,
    ProductType, AttributeDefinition, Product, ProductInstance,
    Order, OrderLine, Payment,
    Location, InventoryItem, InventoryTransaction,
    Carrier, Shipment, ShipmentItem, ShipmentEvent,
  ],
});
