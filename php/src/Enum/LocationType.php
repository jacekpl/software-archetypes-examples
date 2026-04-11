<?php

namespace App\Enum;

enum LocationType: string
{
    case Warehouse = 'Warehouse';
    case Zone = 'Zone';
    case Aisle = 'Aisle';
    case Rack = 'Rack';
    case Shelf = 'Shelf';
    case Bin = 'Bin';
    case Virtual = 'Virtual';
}
