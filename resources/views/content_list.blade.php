@extends('layouts.app')

@section('content')
    <div class="content_header">
        <div class="row">
            <div class="col"><button class="btn" style="float: right;"><span class="glyphicon glyphicon-plus"></span></button></div>
        </div>
        @if(!empty($columns) && is_array($columns))
            <div class="row row_th">
                @foreach ($columns as $column) <div class="col">{{$column}}</div> @endforeach
            </div>
        @endif
    </div>
    <div class="content_content">
        @foreach ($list as $row)
            <div class="row row_td">
                @foreach ($row as $key => $value) <div class="col">
                    @if ($key!=='operations')
                        {{$value}}
                    @else
                        @foreach ($value as $opt => $todo)
                            @if ($opt=='edit')
                                <button class="btn" style="float: right;"><i class="fa fa-edit"></i></button>
                            @endif
                            @if ($opt=='tg_detail')
                                <a href="{{$todo}}" target="_blank" title="Only Read Telegram Service Data">
                                    <button class="btn" style="float: right;"><i class="fa fa-list"></i></button>
                                </a>
                            @endif
                            @if ($opt=='tg_run')
                                <a href="{{$todo}}" target="_blank" title="Get Bot Updates and Clean Telegram Service Data">
                                    <button class="btn" style="float: right;"><i class="fa fa-telegram"></i></button>
                                </a>
                            @endif
                        @endforeach
                    @endif
                </div> @endforeach
            </div>
        @endforeach
    </div>
@endsection
