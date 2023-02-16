<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{$title??""}}</title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="{{asset('css/app.min.css')}}">
    </head>
    <body>
        <div class="row" style="width:100%;height:100%;">
            <div class="col-4 col-md-3 col-lg-2 .col-xl-1 d-md-block bg-light sidebar collapse">
                <a role="button" @if ($active=='hello_world') class="active" @endif href="/hello-world">
                    <div class="sidebar_1"><i class="fa fa-home"></i> Hello World</div>
                </a>
                <a role="button" @if ($active=='about_us') class="active" @endif href="/about_us">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> About Us</div>
                </a>
                <a role="button" @if ($active=='coin') class="active" @endif href="/coin">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Coin</div>
                </a>
                <a role="button" @if ($active=='network') class="active" @endif href="/network">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Network</div>
                </a>
                <a role="button" @if ($active=='coin_network') class="active" @endif href="/coin_network">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Coin Network</div>
                </a>
                <a role="button" @if ($active=='tg_bots') class="active" @endif href="/tg_bot">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Bots</div>
                </a>
                <a role="button" @if ($active=='tg_chats') class="active" @endif href="/tg_bot/chats">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Chats</div>
                </a>
                <a role="button" @if ($active=='tg_users') class="active" @endif href="/tg_bot/users">
                    <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Users</div>
                </a>
            </div>
            <div class="col-8 col-md-9 col-lg-10 .col-xl-11 bg-light container">
                @yield('content')
            </div>
        </div>
    </body>
</html>
