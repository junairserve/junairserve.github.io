@extends('layouts.app')
@section('content')
<h1>一覧/検索</h1>
<div>
  <a class="count-box" href="{{ route('serials.index', ['statuses' => ['UNUSED']]) }}">未使用: {{ $counts['UNUSED'] }}</a>
  <a class="count-box" href="{{ route('serials.index', ['statuses' => ['LINKED']]) }}">紐づけ済み: {{ $counts['LINKED'] }}</a>
  <a class="count-box" href="{{ route('serials.index', ['statuses' => ['INSPECTED']]) }}">検査済み: {{ $counts['INSPECTED'] }}</a>
</div>
<form method="get" action="{{ route('serials.index') }}">
  <label><input type="checkbox" name="statuses[]" value="UNUSED" {{ in_array('UNUSED',$statuses) ? 'checked':'' }}>未使用</label>
  <label><input type="checkbox" name="statuses[]" value="LINKED" {{ in_array('LINKED',$statuses) ? 'checked':'' }}>紐づけ済み</label>
  <label><input type="checkbox" name="statuses[]" value="INSPECTED" {{ in_array('INSPECTED',$statuses) ? 'checked':'' }}>検査済み</label>
  <input type="text" name="q" value="{{ $q }}" placeholder="SN部分一致検索">
  <button type="submit">絞り込み</button>
</form>
<table>
  <thead><tr><th>状態</th><th>本体SN</th><th>基板SN</th><th>最終更新日時</th></tr></thead>
  <tbody>
  @foreach($rows as $row)
    <tr>
      <td>{{ $row->status }}</td>
      <td><a href="{{ route('serials.show', $row->sn) }}">{{ $row->sn }}</a></td>
      <td>{{ $row->pcb_sn }}</td>
      <td>{{ $row->updated_at }}</td>
    </tr>
  @endforeach
  </tbody>
</table>
{{ $rows->links() }}
@endsection
