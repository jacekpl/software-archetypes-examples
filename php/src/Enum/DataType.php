<?php

namespace App\Enum;

enum DataType: string
{
    case String = 'String';
    case Integer = 'Integer';
    case Decimal = 'Decimal';
    case Boolean = 'Boolean';
    case Date = 'Date';
    case Enum = 'Enum';
}
