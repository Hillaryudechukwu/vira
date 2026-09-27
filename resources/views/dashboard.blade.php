@extends('layouts.app')
@section('title', 'VIRA Control Room')
@section('content')
<h1>Content publishing control room</h1>
<p class="muted">Connect TikTok, preview the exact video and caption, then choose draft upload or a separately consented Direct Post.</p>

<div class="grid">
    <section class="card">
        <h2>1. TikTok connection</h2>
        @if(!$tiktokConfigured)
            <div class="error">TikTok credentials are not configured on this environment.</div>
        @elseif(!$tiktokAccount || $tiktokAccount->status !== 'connected')
            <p>No TikTok account is connected.</p>
            <a class="btn" href="{{ route('tiktok.connect') }}">Connect TikTok</a>
        @else
            <div class="account">
                @if($tiktokAccount->avatar_url)<img class="avatar" src="{{ $tiktokAccount->avatar_url }}" alt="TikTok profile">@endif
                <div><strong>{{ $tiktokAccount->display_name ?: 'TikTok creator' }}</strong><br><span class="muted">Connected · {{ implode(', ', $tiktokAccount->scopes) }}</span></div>
            </div>
            @if($creatorError)<div class="error">Creator settings could not be refreshed: {{ $creatorError }}</div>@endif
            <div class="tabs" style="margin-top:14px">
                <form method="post" action="{{ route('tiktok.refresh', $tiktokAccount) }}">@csrf<button class="secondary">Refresh creator settings</button></form>
                <form method="post" action="{{ route('tiktok.disconnect', $tiktokAccount) }}">@csrf @method('DELETE')<button class="danger">Disconnect</button></form>
            </div>
        @endif
    </section>

    <section class="card">
        <h2>2. Recent VIRA projects</h2>
        @forelse($projects as $project)
            <div style="margin-bottom:12px"><strong>{{ $project->working_title }}</strong><br><span class="muted">{{ $project->status->value }} · script v{{ $project->current_script_version }}</span>@if($project->current_script_version > 0)<form method="post" action="{{ route('video.generate', $project) }}" style="margin-top:7px">@csrf<button class="secondary" @disabled(!$automaticGenerationConfigured)>Generate video automatically</button></form>@endif</div>
        @empty
            <p class="muted">No generated project yet. Create the first guarded content package, then review its script before generating the video.</p>
            @if($channels->isNotEmpty())
                <form method="post" action="{{ route('dashboard.automation.run') }}">
                    @csrf
                    <label>Channel</label>
                    <select name="channel_id" required>@foreach($channels as $channel)<option value="{{ $channel->id }}">{{ $channel->name }}</option>@endforeach</select>
                    <button style="margin-top:12px">Create first content package</button>
                </form>
            @else
                <div class="error">No VIRA channel exists. Run <code>php artisan db:seed</code>, then reload this page.</div>
            @endif
        @endforelse
        @if(!$automaticGenerationConfigured)<p class="error">Configure OpenAI, ElevenLabs, and an ElevenLabs voice ID to enable automatic generation.</p>@endif
        @if($projects->isNotEmpty())
        <form method="post" action="{{ route('media.store') }}" enctype="multipart/form-data" style="margin-top:18px">
            @csrf
            <label>Project</label><select name="content_project_id" required>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->working_title }}</option>@endforeach</select>
            <label>Final MP4 or MOV (maximum 50 MB)</label><input type="file" name="video" accept="video/mp4,video/quicktime" required>
            <label><input type="checkbox" name="rights_confirmed" value="1" required> I have the rights and consent required to publish this media.</label>
            <button class="secondary" style="margin-top:12px">Add video to VIRA</button>
        </form>
        @endif
    </section>
</div>

@if($generationRequests->isNotEmpty())<section class="card"><h2>Automatic generation jobs</h2>@foreach($generationRequests as $request)<p><span class="pill">{{ $request->status }}</span> {{ $request->provider }} · {{ $request->created_at->diffForHumans() }} @if($request->error)<span style="color:var(--bad)">{{ $request->error }}</span>@endif</p>@endforeach</section>@endif

@if($tiktokAccount?->status === 'connected')
@php($creator = $tiktokAccount->metadata['creator_info'] ?? [])
<section class="card">
    <h2>3. Preview and destination</h2>
    <label>Select a VIRA video</label>
    <select id="asset-select"><option value="">Choose a video or paste its public URL below</option>@foreach($mediaAssets as $asset)<option value="{{ url($asset->object_key) }}">{{ $asset->content_project_id }} · {{ basename($asset->object_key) }}</option>@endforeach</select>
    <video id="video-preview" controls playsinline style="margin-top:14px"></video>
    <p class="preview-note">Destination: {{ $tiktokAccount->display_name ?: 'Connected TikTok creator' }}. Review the complete video before submitting.</p>
</section>
<div class="grid">
    <section class="card">
        <h2>4. Send to TikTok as draft</h2>
        <p class="muted">TikTok sends an inbox notification. The creator continues editing and publishes inside TikTok.</p>
        <form method="post" action="{{ route('tiktok.drafts.store') }}">
            @csrf
            <input type="hidden" name="social_account_id" value="{{ $tiktokAccount->id }}">
            <label>Public HTTPS video URL</label><input class="video-url" name="video_url" type="url" required placeholder="https://vira.synteric.co.uk/media/demo.mp4" value="{{ old('video_url') }}">
            <label>Caption and hashtags</label><textarea name="caption" rows="4" maxlength="2200">{{ old('caption') }}</textarea>
            <label><input type="checkbox" name="is_aigc" value="1" checked> Mark as AI-generated content</label>
            <label><input type="checkbox" name="rights_confirmed" value="1" required> I confirm I have the rights and consent required for this video.</label>
            <button style="margin-top:12px">Send to TikTok as Draft</button>
        </form>
    </section>

    <section class="card">
        <h2>5. Publish directly to TikTok</h2>
        <p class="muted">Current creator: {{ $creator['creator_nickname'] ?? $tiktokAccount->display_name }}. Maximum duration: {{ $creator['max_video_post_duration_sec'] ?? 'unknown' }} seconds.</p>
        <form method="post" action="{{ route('tiktok.publish.store') }}">
            @csrf
            <input type="hidden" name="social_account_id" value="{{ $tiktokAccount->id }}">
            <label>Public HTTPS video URL</label><input class="video-url" name="video_url" type="url" required placeholder="https://vira.synteric.co.uk/media/demo.mp4" value="{{ old('video_url') }}">
            <label>Caption and hashtags</label><textarea name="caption" rows="4" maxlength="2200">{{ old('caption') }}</textarea>
            <label>Privacy</label><select name="privacy_level" required><option value="">Select an option returned by TikTok</option>@foreach(($creator['privacy_level_options'] ?? []) as $option)<option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>@endforeach</select>
            <label><input type="checkbox" name="disable_comment" value="1" @checked($creator['comment_disabled'] ?? false)> Disable comments</label>
            <label><input type="checkbox" name="disable_duet" value="1" @checked($creator['duet_disabled'] ?? false)> Disable Duet</label>
            <label><input type="checkbox" name="disable_stitch" value="1" @checked($creator['stitch_disabled'] ?? false)> Disable Stitch</label>
            <label><input type="checkbox" name="is_aigc" value="1" checked> Mark as AI-generated content</label>
            <label><input type="checkbox" name="rights_confirmed" value="1" required> I confirm I have the rights and consent required for this video.</label>
            <div class="consent"><label><input type="checkbox" name="consent" value="1" required> I have reviewed this video, caption, destination, disclosure, privacy and interaction settings. Publish it to the connected TikTok account now.</label></div>
            <button style="margin-top:12px">Publish to TikTok</button>
        </form>
    </section>
</div>
@endif

<section class="card wide">
    <h2>Upload and publication status</h2>
    <table><thead><tr><th>Mode</th><th>Caption</th><th>Status</th><th>TikTok ID</th><th>Action</th></tr></thead><tbody>
    @forelse($tiktokPosts as $post)
        <tr><td><span class="pill">{{ $post->mode }}</span></td><td>{{ Str::limit($post->caption, 80) }}</td><td>{{ $post->status }}@if($post->error)<br><span style="color:var(--bad)">{{ $post->error }}</span>@endif</td><td>{{ $post->publish_id }}</td><td>@if($post->publish_id && !in_array($post->status, ['complete','failed']))<form method="post" action="{{ route('tiktok.posts.refresh', $post) }}">@csrf<button class="secondary">Refresh status</button></form>@endif</td></tr>
    @empty<tr><td colspan="5" class="muted">No TikTok submissions yet.</td></tr>@endforelse
    </tbody></table>
</section>
<script>
const selector=document.getElementById('asset-select');const preview=document.getElementById('video-preview');const urls=[...document.querySelectorAll('.video-url')];function selectVideo(url){if(!url)return;preview.src=url;urls.forEach(input=>input.value=url)}selector?.addEventListener('change',event=>selectVideo(event.target.value));urls.forEach(input=>input.addEventListener('change',event=>selectVideo(event.target.value)));
</script>
@endsection
