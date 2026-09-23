<?php

namespace App\Modules\Production\Enums;

enum ProviderJobStatus: string
{
    case AwaitingManualAction = 'awaiting_manual_action';
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Complete = 'complete';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
