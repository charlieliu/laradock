@extends('layouts.app')

@section('content')
    @if(!empty($columns) && is_array($columns))
        <div class="row">
            @foreach ($columns as $column) <div class="col">{{$column}}</div> @endforeach
            <div class="col"></div>
        </div>
    @endif
    @foreach ($list as $row)
        <div class="row">
            @foreach ($row as $value) <div class="col">{{$value}}</div> @endforeach
            <div class="col"><button class="btn"><i class="fa fa-edit"></i></button></div>
        </div>
    @endforeach
@endsection
