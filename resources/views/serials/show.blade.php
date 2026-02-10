@extends('layouts.app')
@section('content')
<h1>本体SN: {{ $serial->sn }}</h1>
<p>状態: {{ $serial->status }}</p>

<h2>タイムライン</h2>
<ul>
  <li>受入: {{ $serial->created_at }}</li>
  @foreach($links as $link)
    <li>紐づけ: {{ $link->linked_at }} / PCB {{ $link->pcb_sn }}</li>
    @if($link->unlinked_at)
      <li>取消: {{ $link->unlinked_at }} / 理由 {{ $link->unlink_reason }}</li>
    @endif
  @endforeach
  @foreach($inspections as $insp)
    <li>検査: {{ $insp->created_at }} / {{ $insp->result }}</li>
  @endforeach
</ul>

<h2>監査ログ</h2>
<table>
  <thead><tr><th>日時</th><th>操作</th><th>理由</th><th>actor</th></tr></thead>
  <tbody>
  @foreach($audits as $audit)
    <tr><td>{{ $audit->created_at }}</td><td>{{ $audit->action }}</td><td>{{ $audit->reason }}</td><td>{{ $audit->actor_user_id }}</td></tr>
  @endforeach
  </tbody>
</table>
@endsection
