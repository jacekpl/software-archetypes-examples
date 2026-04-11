<?php

namespace App\Enum;

enum ShipmentStatus: string
{
    case Created = 'Created';
    case Picking = 'Picking';
    case Packed = 'Packed';
    case LabelPrinted = 'LabelPrinted';
    case HandedOver = 'HandedOver';
    case InTransit = 'InTransit';
    case OutForDelivery = 'OutForDelivery';
    case Delivered = 'Delivered';
    case DeliveryFailed = 'DeliveryFailed';
    case Returned = 'Returned';
    case Cancelled = 'Cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled]);
    }
}
