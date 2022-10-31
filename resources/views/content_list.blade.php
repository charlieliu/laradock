@extends('layouts.app')

@section('content')
    <div class="content_header">
        <div class="row">
            <div class="col">
                @foreach ($breadcrumbs as $name => $href)
                <a href="{{ $href }}" style="margin-right: 4px;float: left;">{{ $name }}</a>
                @endforeach
            </div>
            <div class="col">
                <button class="btn"><span class="fa fa-undo" onclick="history.back()"></span></button>
            </div>
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
                                <button class="btn"><i class="fa fa-edit"></i></button>
                            @endif
                            @if ($opt=='tg_detail')
                                <a href="{{$todo}}" title="Only Read Telegram Service Data">
                                    <button class="btn"><i class="fa fa-list"></i></button>
                                </a>
                            @endif
                            @if ($opt=='tg_run')
                                <a href="{{$todo}}" title="Get Bot Updates and Clean Telegram Service Data">
                                    <button class="btn"><i class="fa fa-ambulance"></i></button>
                                </a>
                            @endif
                            @if ($opt=='tg_chat_messages' || $opt=='tg_user_messages')
                                <a href="{{$todo}}" title="Messages">
                                    <button class="btn"><i class="fa fa-comment"></i></button>
                                </a>
                            @endif
                            @if ($opt=='tg_send')
                                <a href="{{$todo}}" title="Get Bot Updates and Clean Telegram Service Data">
                                    <button class="btn"><i class="fa fa-telegram"></i></button>
                                </a>
                            @endif
                        @endforeach
                    @endif
                </div> @endforeach
            </div>
        @endforeach
    </div>
@endsection
