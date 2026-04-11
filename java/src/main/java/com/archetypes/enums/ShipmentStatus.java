package com.archetypes.enums;

import java.util.Set;

public enum ShipmentStatus {
    Created, Picking, Packed, LabelPrinted, HandedOver, InTransit,
    OutForDelivery, Delivered, DeliveryFailed, Returned, Cancelled;

    private static final Set<ShipmentStatus> FINAL_STATUSES = Set.of(Delivered, Returned, Cancelled);

    public boolean isFinal() {
        return FINAL_STATUSES.contains(this);
    }
}
