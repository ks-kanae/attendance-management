<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-login.css') }}">
</head>

<body>

<div class="login-container">
    <div class="login-content">
        <h1 class="login-title">管理者ログイン</h1>

        <form class="login-form" action="{{ route('admin.login') }}" method="POST" novalidate>
            @csrf
            <div class="form-group">
                <label class="form-label">メールアドレス</label>
                <input type="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus>
                @error('email')
                <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">パスワード</label>
                <input type="password" name="password" class="form-input" required>
                @error('password')
                <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="login-button">
                管理者ログインする
            </button>
        </form>
    </div>
</div>

</body>
</html>
