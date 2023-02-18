@extends('layouts.app')

@section('content')
    @foreach ($list as $row)
        <div class="row row_td">
            @foreach ($row as $key => $value) <div class="col">
                @if ($key!=='operations')
                    {{ $value }}
                @else
                    @foreach ($value as $opt => $todo)
                        @if ($opt=='edit')
                            <button class="btn"><i class="fa fa-edit"></i></button>
                        @endif
                        @if ($opt=='tg_bot_edit')
                            <a href="{{ $todo }}">
                                <button class="btn"><i class="fa fa-edit"></i></button>
                            </a>
                        @endif
                        @if ($opt=='tg_chat_messages' || $opt=='tg_user_messages')
                            <a href="{{ $todo }}" title="Message List">
                                <button class="btn"><i class="fa fa-list"></i></button>
                            </a>
                        @endif
                        @if ($opt=='tg_sync')
                            <a href="{{ $todo }}" title="Pull Telegram Data">
                                <button class="btn"><i class="fa fa-cloud-download"></i></button>
                            </a>
                        @endif
                        @if ($opt=='tg_link')
                            <a href="{{ $todo }}" target="_blank">
                                <button class="btn"><i class="fa fa-telegram""></i></button>
                            </a>
                        @endif
                    @endforeach
                @endif
            </div> @endforeach
        </div>
    @endforeach
@endsection
