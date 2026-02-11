@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-content">
        {{-- 状態バッジ --}}
        <div class="status-badge status-badge--{{ $status }}">
            @if($status === 'not_working')
                勤務外
            @elseif($status === 'working')
                出勤中
            @elseif($status === 'on_break')
                休憩中
            @elseif($status === 'finished')
                退勤済
            @endif
        </div>

        {{-- 日時表示 --}}
        <div class="date-time">
            <p class="current-date" id="current-date"></p>
            <p class="current-time" id="current-time"></p>
        </div>

        {{-- ボタン表示 --}}
        <div class="button-wrapper">
            @if($status === 'not_working')
                {{-- 出勤ボタンのみ --}}
                <form class="attendance-form" action="{{ route('attendance.start') }}" method="post">
                    @csrf
                    <button class="attendance-button attendance-button--single" type="submit">
                        出勤
                    </button>
                </form>

            @elseif($status === 'working')
                {{-- 退勤・休憩入ボタン --}}
                <form class="attendance-form" action="{{ route('attendance.end') }}" method="post">
                    @csrf
                    <button class="attendance-button attendance-button--end" type="submit">
                        退勤
                    </button>
                </form>
                <form class="attendance-form" action="{{ route('attendance.break-start') }}" method="post">
                    @csrf
                    <button class="attendance-button attendance-button--break" type="submit">
                        休憩入
                    </button>
                </form>

            @elseif($status === 'on_break')
                {{-- 休憩戻ボタンのみ --}}
                <form class="attendance-form" action="{{ route('attendance.break-end') }}" method="post">
                    @csrf
                    <button class="attendance-button attendance-button--single attendance-button--break-end" type="submit">
                        休憩戻
                    </button>
                </form>

            @elseif($status === 'finished')
                {{-- 退勤済メッセージ --}}
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>

<script>
    // リアルタイム時計
    function updateDateTime() {
        const now = new Date();

        // 日付のフォーマット
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const date = now.getDate();
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        const day = dayNames[now.getDay()];

        document.getElementById('current-date').textContent =
            `${year}年${month}月${date}日(${day})`;

        // 時刻のフォーマット
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('current-time').textContent = `${hours}:${minutes}`;
    }

    // 初回実行
    updateDateTime();

    // 1秒ごとに更新
    setInterval(updateDateTime, 1000);
</script>
@endsection
