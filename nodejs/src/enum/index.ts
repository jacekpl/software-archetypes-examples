export enum Gender {
  Male = "Male",
  Female = "Female",
  Other = "Other",
  Unknown = "Unknown",
}

export enum AddressType {
  Home = "Home",
  Work = "Work",
  Billing = "Billing",
  Shipping = "Shipping",
}

export enum LoyaltyTier {
  Bronze = "Bronze",
  Silver = "Silver",
  Gold = "Gold",
  Platinum = "Platinum",
}

export enum SupplierRating {
  A = "A",
  B = "B",
  C = "C",
  D = "D",
}

export enum EmploymentType {
  FullTime = "FullTime",
  PartTime = "PartTime",
  Contract = "Contract",
}

export enum ProductStatus {
  Draft = "Draft",
  Active = "Active",
  Discontinued = "Discontinued",
  Deleted = "Deleted",
}

export enum InstanceStatus {
  Available = "Available",
  Reserved = "Reserved",
  Sold = "Sold",
  Damaged = "Damaged",
  Expired = "Expired",
  Returned = "Returned",
}

export enum DataType {
  String = "String",
  Integer = "Integer",
  Decimal = "Decimal",
  Boolean = "Boolean",
  Date = "Date",
  Enum = "Enum",
}

export enum OrderStatus {
  Draft = "Draft",
  Confirmed = "Confirmed",
  Paid = "Paid",
  Processing = "Processing",
  Shipped = "Shipped",
  Delivered = "Delivered",
  Completed = "Completed",
  Cancelled = "Cancelled",
}

export enum PaymentMethod {
  Card = "Card",
  BankTransfer = "BankTransfer",
  Blik = "Blik",
  PayPal = "PayPal",
  Cash = "Cash",
  Invoice = "Invoice",
}

export enum PaymentStatus {
  Pending = "Pending",
  Processing = "Processing",
  Completed = "Completed",
  Failed = "Failed",
  Cancelled = "Cancelled",
  Refunded = "Refunded",
  PartiallyRefunded = "PartiallyRefunded",
}

export enum LocationType {
  Warehouse = "Warehouse",
  Zone = "Zone",
  Aisle = "Aisle",
  Rack = "Rack",
  Shelf = "Shelf",
  Bin = "Bin",
  Virtual = "Virtual",
}

export enum TransactionType {
  Receipt = "Receipt",
  Issue = "Issue",
  Reserve = "Reserve",
  Release = "Release",
  TransferOut = "TransferOut",
  TransferIn = "TransferIn",
  Adjustment = "Adjustment",
  Return = "Return",
}

export enum ShipmentStatus {
  Created = "Created",
  Picking = "Picking",
  Packed = "Packed",
  LabelPrinted = "LabelPrinted",
  HandedOver = "HandedOver",
  InTransit = "InTransit",
  OutForDelivery = "OutForDelivery",
  Delivered = "Delivered",
  DeliveryFailed = "DeliveryFailed",
  Returned = "Returned",
  Cancelled = "Cancelled",
}

export function isShipmentStatusFinal(status: ShipmentStatus): boolean {
  return [ShipmentStatus.Delivered, ShipmentStatus.Returned, ShipmentStatus.Cancelled].includes(status);
}
