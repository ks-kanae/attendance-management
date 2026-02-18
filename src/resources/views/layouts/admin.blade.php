<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '管理画面') - COACHTECH勤怠管理</title>

    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-common.css') }}">
    @yield('css')
</head>

<body>
    <header class="admin-header">
        <div class="admin-header-inner">
            <div class="admin-header-logo">
                <img src="{{ asset('img/COACHTECHヘッダーロゴ.png') }}" alt="COACHTECH">
            </div>
            <nav class="admin-header-nav">
                <a href="{{ route('admin.attendance.list') }}" class="nav-link">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}" class="nav-link">スタッフ一覧</a>
                <a href="{{ route('admin.correction.list') }}" class="nav-link">申請一覧</a>
                <form action="{{ route('admin.logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-link nav-link--button">ログアウト</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="admin-main">
        @yield('content')
    </main>

</body>

</html>
