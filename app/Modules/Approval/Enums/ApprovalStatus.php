<?php

namespace App\Modules\Approval\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';
    case Expired = 'expired';
    case Invalidated = 'invalidated';
}
