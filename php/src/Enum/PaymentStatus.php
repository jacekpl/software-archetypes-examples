<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case Processing = 'Processing';
    case Completed = 'Completed';
    case Failed = 'Failed';
    case Cancelled = 'Cancelled';
    case Refunded = 'Refunded';
    case PartiallyRefunded = 'PartiallyRefunded';
}
