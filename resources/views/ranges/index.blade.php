@extends('layouts.app')
@section('content')
<h1>連番レンジ受入</h1>
<form method="post" action="{{ route('ranges.store') }}">
  @csrf
  <select name="type" required>
    <option value="BODY">本体</option>
    <option value="PCB">基板</option>
  </select>
  <input type="text" name="start_sn" maxlength="8" pattern="[0-9]{8}" placeholder="開始SN" required>
  <input type="text" name="end_sn" maxlength="8" pattern="[0-9]{8}" placeholder="終了SN" required>
  <input type="text" name="lot_name" maxlength="100" placeholder="ロット名(任意)">
  <button type="submit">受入実行</button>
</form>
@endsection
