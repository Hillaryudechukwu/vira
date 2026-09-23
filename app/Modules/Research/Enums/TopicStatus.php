<?php

namespace App\Modules\Research\Enums;

enum TopicStatus: string
{
    case Discovered = 'discovered';
    case Qualified = 'qualified';
    case Rejected = 'rejected';
    case Selected = 'selected';
    case Expired = 'expired';
}
