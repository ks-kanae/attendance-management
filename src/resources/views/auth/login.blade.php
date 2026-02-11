@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-form-content">
    <div class="login-form-heading">
        <h2>ログイン</h2>
    </div>
    <form class="form" action="/login" method="post" novalidate>
    @csrf
        <div class="form-group">
            <div class="form-group-title">
                <span class="form-label-item">メールアドレス</span>
            </div>
            <div class="form-group-content">
                <div class="form-input-text">
                    <input type="email" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form-error">
                    @error('email')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="form-group-title">
                <span class="form-label-item">パスワード</span>
            </div>
            <div class="form-group-content">
                <div class="form-input-text">
                    <input type="password" name="password" />
                </div>
                <div class="form-error">
                    @error('password')
                    {{ $message }}
                    @enderror
                </div>
            </div>
        </div>
        <div class="form-button">
            <button class="form-button-submit" type="submit">ログインする</button>
        </div>
    </form>
    <div class="register-link">
        <a class="register-button-submit" href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection
