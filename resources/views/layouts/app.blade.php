<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{$title??""}}</title>
    </head>
    <body>
        <div class="sidebar" style="width: 15%; float: left; background-color:#aaa;">
            測邊欄
        </div>
        <div class="container">
            @yield('content')
        </div>
    </body>
</html>
