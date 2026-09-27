<?php

namespace App\Http\Controllers;

use App\Models\ContentProject;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class MediaUploadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content_project_id' => ['required', 'uuid', 'exists:content_projects,id'],
            'video' => ['required', 'file', 'mimes:mp4,mov', 'max:51200'],
            'rights_confirmed' => ['accepted'],
        ]);

        $project = ContentProject::query()->findOrFail($data['content_project_id']);
        $file = $data['video'];
        $name = Str::uuid().'.'.$file->guessExtension();
        $directory = public_path('media');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $file->move($directory, $name);
        $path = $directory.'/'.$name;

        MediaAsset::query()->create([
            'content_project_id' => $project->id,
            'asset_type' => 'master',
            'disk' => 'public',
            'object_key' => 'media/'.$name,
            'mime_type' => mime_content_type($path) ?: 'video/mp4',
            'byte_size' => filesize($path),
            'checksum' => hash_file('sha256', $path),
            'provenance' => ['source' => 'operator_upload', 'original_name' => $file->getClientOriginalName()],
            'rights_status' => 'operator_confirmed',
        ]);

        return back()->with('status', 'Video uploaded and ready for preview and TikTok delivery.');
    }
}
