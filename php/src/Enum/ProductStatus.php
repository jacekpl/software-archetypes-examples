<?php

namespace App\Enum;

enum ProductStatus: string
{
    case Draft = 'Draft';
    case Active = 'Active';
    case Discontinued = 'Discontinued';
    case Deleted = 'Deleted';
}
