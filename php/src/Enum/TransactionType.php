<?php

namespace App\Enum;

enum TransactionType: string
{
    case Receipt = 'Receipt';
    case Issue = 'Issue';
    case Reserve = 'Reserve';
    case Release = 'Release';
    case TransferOut = 'TransferOut';
    case TransferIn = 'TransferIn';
    case Adjustment = 'Adjustment';
    case Return = 'Return';
}
