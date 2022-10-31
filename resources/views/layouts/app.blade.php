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
        <div class="col-4 col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <a role="button" @if ($active=='hello_world') class="active" @endif href="/hello-world">
                <div class="sidebar_1"><i class="fa fa-home"></i> Hello World</div>
            </a>
            <a role="button" @if ($active=='about_us') class="active" @endif href="/about_us">
                <div class="sidebar_1"><i class="fa fa-bars"></i> About Us</div>
            </a>
            <a role="button" @if ($active=='inspire') class="active" @endif href="/inspire">
                <div class="sidebar_1"><i class="fa fa-bars"></i> Inspire</div>
            </a>
            <a role="button" @if ($active=='tb_bots') class="active" @endif href="/tb">
                <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Bots</div>
            </a>
            <a role="button" @if ($active=='tb_chats') class="active" @endif href="/tb/chats">
                <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Chats</div>
            </a>
            <a role="button" @if ($active=='tb_users') class="active" @endif href="/tb/users">
                <div class="sidebar_1"><i class="fa fa-bars"></i> Telegram Users</div>
            </a>
        </div>
        <div class="col-8 col-md-9 col-lg-10 bg-light container">
            @yield('content')
        </div>
    </body>
</html>
