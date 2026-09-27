<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'VIRA Control Room')</title>
    <style>
        :root{--bg:#080d1a;--panel:#111a2e;--panel2:#17223b;--text:#eef3ff;--muted:#9cabc9;--line:#293858;--accent:#7c9cff;--ok:#4fd1a1;--bad:#ff778b}*{box-sizing:border-box}body{margin:0;background:linear-gradient(145deg,#080d1a,#0c1428);color:var(--text);font:15px/1.55 Inter,system-ui,sans-serif}a{color:#adc0ff}.shell{max-width:1180px;margin:auto;padding:22px}.top{display:flex;align-items:center;gap:18px;border-bottom:1px solid var(--line);padding-bottom:18px}.brand{font-size:1.25rem;font-weight:800;color:#fff;text-decoration:none;margin-right:auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px}.card{background:rgba(17,26,46,.94);border:1px solid var(--line);border-radius:16px;padding:20px;margin:18px 0}.wide{grid-column:1/-1}h1,h2,h3{line-height:1.2}h2{font-size:1.15rem;margin-top:0}.muted{color:var(--muted)}.status{padding:12px 15px;border-radius:10px;background:#15372f;border:1px solid #236b58;margin:16px 0}.error{padding:12px 15px;border-radius:10px;background:#3b1922;border:1px solid #7d2c3e;margin:16px 0}.btn,button{display:inline-block;border:0;border-radius:10px;padding:10px 14px;background:var(--accent);color:#071127;font-weight:750;text-decoration:none;cursor:pointer}.secondary{background:#263654;color:var(--text)}.danger{background:#6d2435;color:#fff}label{display:block;font-weight:650;margin:12px 0 5px}input,textarea,select{width:100%;border:1px solid var(--line);border-radius:9px;background:#091125;color:var(--text);padding:10px}input[type=checkbox]{width:auto;margin-right:7px}video{width:100%;max-height:520px;background:#000;border-radius:12px}.account{display:flex;gap:14px;align-items:center}.avatar{width:54px;height:54px;border-radius:50%;object-fit:cover;background:var(--panel2)}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:9px;border-bottom:1px solid var(--line);vertical-align:top}.pill{display:inline-block;border:1px solid var(--line);border-radius:999px;padding:3px 9px;color:var(--muted);font-size:.8rem}.consent{background:#101d36;border:1px solid #3b5482;padding:12px;border-radius:10px;margin-top:12px}.tabs{display:flex;gap:8px;flex-wrap:wrap}.preview-note{font-size:.86rem;color:var(--muted)}
    </style>
</head>
<body>
<div class="shell">
    <header class="top"><a class="brand" href="{{ route('dashboard') }}">VIRA Control Room</a><a href="{{ route('home') }}">Public site</a>@if(session('vira_operator_authenticated'))<form method="post" action="{{ route('operator.logout') }}">@csrf<button class="secondary">Sign out</button></form>@endif</header>
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error"><strong>Action could not be completed.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</div>
</body>
</html>
