<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{$title??""}}</title>        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
        <style>
            html{line-height:1.15;-webkit-text-size-adjust:100%}
            body{margin:0;font-family: 'Nunito', sans-serif;height: 100vh;max-height: calc(100vh - 16px);width: 100vw;}
            .sidebar{
                padding: 8px;
                height:100%;
                float: left;
                border-radius: 5px;
            }
            .sidebar .active, .active .sidebar_1 {
                color: white;
                background-color: #04AA6D;
            }
            .sidebar a{
                color: #04AA6D;
                text-decoration: none;
                background-color:transparent;
            }
            .sidebar_1{
                margin: 8px;
                padding: 8px;
                width: calc(100% - 16px);
                border-radius: 5px;
                box-shadow: 0 2px 5px 0 rgb(0 0 0 / 16%), 0 2px 10px 0 rgb(0 0 0 / 12%);
            }
            .sidebar a:hover:not(.active) .sidebar_1 {
                background-color: #555;
                color: white;
            }
            .container{
                height:100%;
                padding: 16px;
                box-shadow: 0 2px 5px 0 rgb(0 0 0 / 5%), 0 2px 10px 0 rgb(0 0 0 / 5%);
                border-radius: 16px;
                float: left;
            }
            .container .row{
                padding: 8px;
                box-shadow: 0 2px 5px 0 rgb(0 0 0 / 5%), 0 2px 10px 0 rgb(0 0 0 / 5%);
                border-radius: 8px;
            }
            .container .row:hover{
                background-color: wheat;
            }
        </style>
    </head>
    <body>
        <div class="col-4 col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <a role="button" @if ($active=='hello_world') class="active" @endif href="/hello-world"><div class="sidebar_1">Hello World</div></a>
            <a role="button" @if ($active=='about_us') class="active" @endif href="/about_us"><div class="sidebar_1">About Us</div></a>
            <a role="button" @if ($active=='inspire') class="active" @endif href="/inspire"><div class="sidebar_1">Inspire</div></a>
        </div>
        <div class="col-8 col-md-9 col-lg-10 bg-light container">
            @yield('content')
        </div>
    </body>
</html>
