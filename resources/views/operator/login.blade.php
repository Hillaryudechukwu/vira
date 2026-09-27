@extends('layouts.app')
@section('title', 'VIRA Operator Sign in')
@section('content')
<div class="card" style="max-width:520px;margin:70px auto">
    <h1>Operator sign in</h1>
    <p class="muted">Use the private VIRA operator token. TikTok reviewers should receive a temporary review credential rather than a production secret.</p>
    <form method="post" action="{{ route('operator.login.store') }}">
        @csrf
        <label for="token">Operator token</label>
        <input id="token" name="token" type="password" required autofocus autocomplete="current-password">
        <button style="margin-top:16px">Open control room</button>
    </form>
</div>
@endsection
