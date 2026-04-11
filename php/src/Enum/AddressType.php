<?php

namespace App\Enum;

enum AddressType: string
{
    case Home = 'Home';
    case Work = 'Work';
    case Billing = 'Billing';
    case Shipping = 'Shipping';
}
