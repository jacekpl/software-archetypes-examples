<?php

namespace App\Enum;

enum Gender: string
{
    case Male = 'Male';
    case Female = 'Female';
    case Other = 'Other';
    case Unknown = 'Unknown';
}
