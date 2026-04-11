<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Draft = 'Draft';
    case Confirmed = 'Confirmed';
    case Paid = 'Paid';
    case Processing = 'Processing';
    case Shipped = 'Shipped';
    case Delivered = 'Delivered';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
