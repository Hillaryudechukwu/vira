<?php

namespace App\Modules\Publishing\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case Queued = 'queued';
    case Initialising = 'initialising';
    case Uploading = 'uploading';
    case Processing = 'processing';
    case Published = 'published';
    case FailedRetryable = 'failed_retryable';
    case FailedTerminal = 'failed_terminal';
    case Cancelled = 'cancelled';
    case ManualHandoff = 'manual_handoff';
}
