<?php

namespace Modules\Payroll\Enums;

enum PayrollPeriodStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Finalized = 'finalized';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
