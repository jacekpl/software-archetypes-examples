<?php

namespace App\Enum;

enum InstanceStatus: string
{
    case Available = 'Available';
    case Reserved = 'Reserved';
    case Sold = 'Sold';
    case Damaged = 'Damaged';
    case Expired = 'Expired';
    case Returned = 'Returned';
}
