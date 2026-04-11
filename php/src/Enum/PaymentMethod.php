<?php

namespace App\Enum;

enum PaymentMethod: string
{
    case Card = 'Card';
    case BankTransfer = 'BankTransfer';
    case Blik = 'Blik';
    case PayPal = 'PayPal';
    case Cash = 'Cash';
    case Invoice = 'Invoice';
}
