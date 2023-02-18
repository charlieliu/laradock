@extends('layouts.app')

@section('content')
<form method="POST" action="{{ $form_action }}">
    @csrf
    @foreach ($detail as $key => $row)
    <div class="form-group">
        <label for="input-{{ $key }}" style="float:left;padding-left:5px;">{{ $row['name'] }}:</label>
        <input id="input-{{ $key }}" type="text" class="form-control" name="{{ $key }}" value="{{ $row['value'] }}" @if($row['disabled']) disabled="disabled" @endif >
      </div>
    @endforeach
    <div class="form-group">
        <button type="sumbit" class="btn" style="float:left;margin-left:calc(50% - 6px)">
            <i class="fa fa-floppy-o" aria-hidden="true"></i>
        </button>
    </div>
</form>
@endsection
