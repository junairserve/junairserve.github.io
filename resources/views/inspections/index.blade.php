@extends('layouts.app')
@section('content')
<h1>検査記録</h1>
<form method="post" action="{{ route('inspections.store') }}">
  @csrf
  <input type="text" name="body_sn" maxlength="8" pattern="[0-9]{8}" placeholder="本体SN" required>
  <input type="text" name="cert_no" placeholder="工事設計認証番号" required>
  <input type="date" name="date" required>
  <input type="text" name="place" placeholder="検査場所" required>
  <input type="text" name="method" placeholder="検査方法" required>
  <select name="result"><option value="PASS">PASS</option><option value="FAIL">FAIL</option></select>
  <button type="submit">登録</button>
</form>
<table>
  <thead><tr><th>ID</th><th>本体SN</th><th>日付</th><th>結果</th><th>責任者</th></tr></thead>
  <tbody>
    @foreach($inspections as $r)
      <tr><td>{{ $r->id }}</td><td>{{ $r->body_sn }}</td><td>{{ $r->date }}</td><td>{{ $r->result }}</td><td>{{ $r->responsible_user_id }}</td></tr>
    @endforeach
  </tbody>
</table>
{{ $inspections->links() }}
@endsection
