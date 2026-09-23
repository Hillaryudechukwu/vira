<?php

namespace App\Modules\Editorial\Enums;

enum ContentProjectStatus: string
{
    case Researching = 'researching';
    case Drafting = 'drafting';
    case Producing = 'producing';
    case QualityReview = 'quality_review';
    case AwaitingApproval = 'awaiting_approval';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Live = 'live';
    case Analysing = 'analysing';
    case Complete = 'complete';
    case Failed = 'failed';
}
