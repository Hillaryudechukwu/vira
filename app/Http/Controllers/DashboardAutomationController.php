<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Modules\Automation\Application\RunGuardedAutomation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardAutomationController extends Controller
{
    public function store(Request $request, RunGuardedAutomation $automation): RedirectResponse
    {
        $data = $request->validate([
            'channel_id' => ['required', 'uuid', 'exists:channels,id'],
        ]);

        $run = $automation->execute(Channel::query()->findOrFail($data['channel_id']));

        return to_route('dashboard')->with(
            'status',
            "Content package {$run->contentProject->working_title} is ready for review. You can now generate its video.",
        );
    }
}
