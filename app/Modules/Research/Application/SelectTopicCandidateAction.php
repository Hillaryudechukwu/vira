<?php

namespace App\Modules\Research\Application;

use App\Models\ContentProject;
use App\Models\TopicCandidate;
use App\Modules\Editorial\Enums\ContentProjectStatus;
use App\Modules\Research\Enums\TopicStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SelectTopicCandidateAction
{
    public function execute(TopicCandidate $topic): ContentProject
    {
        if (! in_array($topic->status, [TopicStatus::Discovered, TopicStatus::Qualified], true)) {
            throw ValidationException::withMessages(['topic' => 'Only discovered or qualified topics may be selected.']);
        }

        if ((float) $topic->viral_score < (float) config('vira.topic_score_threshold')) {
            throw ValidationException::withMessages(['topic' => 'Topic is below the configured qualification threshold.']);
        }

        return DB::transaction(function () use ($topic): ContentProject {
            $topic->update(['status' => TopicStatus::Selected]);

            return ContentProject::query()->create([
                'channel_id' => $topic->channel_id,
                'topic_candidate_id' => $topic->id,
                'working_title' => $topic->title,
                'objective' => $topic->angle,
                'status' => ContentProjectStatus::Drafting,
                'target_duration_ms' => 45000,
                'aspect_ratio' => '9:16',
                'language' => 'en-GB',
            ]);
        });
    }
}
