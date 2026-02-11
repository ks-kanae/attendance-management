<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <div class="header-utilities">
                <a href="{{ route('attendance') }}">
                    <img class="header-logo" src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}">
                </a>

                <ul class="header-nav">
                    @if (Auth::check())
                    <li class="header-nav-item">
                        <a class="header-nav-link" href="{{ route('attendance') }}">勤怠</a>
                    </li>
                    <li class="header-nav-item">
                        <a class="header-nav-link" href="{{ route('attendance.list') }}">勤怠一覧</a>
                    </li>
                    <li class="header-nav-item">
                        <a class="header-nav-link" href="{{ route('correction.list') }}">申請</a>
                    </li>
                    <li class="header-nav-item">
                        <form class="form" action="/logout" method="post">
                        @csrf
                            <button class="header-nav-button">ログアウト</button>
                        </form>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

</body>

</html>
