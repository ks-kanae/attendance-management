@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="mail-container">
    <div class="mail-content">
        <p class="mail-message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        @if(app()->environment('local'))
        <button
        class="mail-button"
        onclick="location.href='http://localhost:8025'">認証はこちらから
        </button>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="mail-resend-link">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection
