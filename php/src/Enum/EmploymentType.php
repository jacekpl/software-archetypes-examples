<?php

namespace App\Enum;

enum EmploymentType: string
{
    case FullTime = 'FullTime';
    case PartTime = 'PartTime';
    case Contract = 'Contract';
}
