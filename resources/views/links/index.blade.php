@extends('layouts.app')
@section('content')
<h1>紐づけ（高速入力）</h1>
<form method="post" action="{{ route('links.store') }}" id="link-form">
  @csrf
  <input id="body_sn" type="text" name="body_sn" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" placeholder="本体SN" required autofocus>
  <input id="pcb_sn" type="text" name="pcb_sn" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" placeholder="基板SN" required>
  <button type="submit">紐づけ</button>
</form>

<h2>直近20件</h2>
<table>
  <thead><tr><th>時刻</th><th>本体SN</th><th>基板SN</th><th>結果</th><th>実行者</th><th>取消</th></tr></thead>
  <tbody>
    @foreach($latest as $row)
      <tr>
        <td>{{ $row->linked_at }}</td>
        <td>{{ $row->body_sn }}</td>
        <td>{{ $row->pcb_sn }}</td>
        <td>{{ $row->unlinked_at ? '取消済み' : '紐づけ済み' }}</td>
        <td>{{ $row->linked_by_user_id }}</td>
        <td>
          @if(!$row->unlinked_at)
          <form method="post" action="{{ route('links.cancel', $row) }}">
            @csrf
            <input type="text" name="reason" required maxlength="255" placeholder="取消理由">
            <button type="submit">取消</button>
          </form>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
<script>
const body = document.getElementById('body_sn');
const pcb = document.getElementById('pcb_sn');
body.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); pcb.focus(); } });
</script>
@endsection
