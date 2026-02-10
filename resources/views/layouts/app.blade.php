<!doctype html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SN管理MVP</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { position: sticky; top: 0; background: #fafafa; }
    .count-box { display: inline-block; padding: 8px 12px; border: 1px solid #aaa; margin-right: 8px; }
    .error { color: #b91c1c; }
    .ok { color: #15803d; }
    input[type=text], input[type=date], select { padding: 6px; }
  </style>
</head>
<body>
<nav>
  <a href="{{ route('serials.index') }}">一覧</a> |
  <a href="{{ route('ranges.index') }}">受入</a> |
  <a href="{{ route('links.index') }}">紐づけ</a> |
  <a href="{{ route('inspections.index') }}">検査</a>
</nav>
@if(session('status'))<p class="ok">{{ session('status') }}</p>@endif
@if($errors->any())
  <ul class="error">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
@endif
@yield('content')
</body>
</html>
