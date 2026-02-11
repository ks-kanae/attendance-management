@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
<div class="detail-container">
    <div class="detail-content">
        <h1 class="page-title">勤怠詳細</h1>

        @if($hasPendingCorrection)
            {{-- 承認待ち表示 --}}
            <div class="detail-card detail-card--readonly">
                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value">{{ $attendance->user->name }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">日付</div>
                    <div class="detail-value">{{ $attendance->date->format('Y年') }}　{{ $attendance->date->format('n月j日') }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-value-group">
                        <span class="time-display">{{ $attendance->latestCorrection->corrected_start_time ? \Carbon\Carbon::parse($attendance->latestCorrection->corrected_start_time)->format('H:i') : \Carbon\Carbon::parse($attendance->start_time)->format('H:i')}}
                        </span>
                        <span class="time-separator">〜</span>
                        <span class="time-display">{{ $attendance->latestCorrection->corrected_end_time ? \Carbon\Carbon::parse($attendance->latestCorrection->corrected_end_time)->format('H:i') : \Carbon\Carbon::parse($attendance->end_time)->format('H:i')}}
                        </span>
                    </div>
                </div>
                @php
                    $breaks = $attendance->breaks;
                    $breakCorrections = $attendance->latestCorrection->breakCorrections
                        ->keyBy('break_number');
                @endphp

                @foreach($breaks as $index => $break)
                    @php
                        $breakNumber = $index + 1;
                        $correction = $breakCorrections->get($breakNumber);

                        $start = $correction->corrected_start_time ?? $break->start_time;
                        $end   = $correction->corrected_end_time   ?? $break->end_time;
                    @endphp

                    <div class="detail-row">
                        <div class="detail-label">休憩{{ $breakNumber > 1 ? $breakNumber : '' }}</div>
                        <div class="detail-value-group">
                            <span class="time-display">{{ $start ? \Carbon\Carbon::parse($start)->format('H:i') : '' }}</span>
                            <span class="time-separator">〜</span>
                            <span class="time-display">{{ $end ? \Carbon\Carbon::parse($end)->format('H:i') : '' }}</span>
                        </div>
                    </div>
                @endforeach

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value">{{ $attendance->latestCorrection->reason }}</div>
                </div>
            </div>

            <div class="pending-message">
                ＊承認待ちのため修正はできません。
            </div>

        @else
            {{-- 修正可能フォーム --}}
            <form action="{{ route('attendance.correct', $attendance->id) }}" method="POST" class="detail-form">
                @csrf
                <div class="detail-card">
                    <div class="detail-row">
                        <div class="detail-label">名前</div>
                        <div class="detail-value">{{ $attendance->user->name }}</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">日付</div>
                        <div class="detail-value">{{ $attendance->date->format('Y年') }}　{{ $attendance->date->format('n月j日') }}</div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">出勤・退勤</div>
                        <div class="detail-value-group">
                            <input type="time" name="corrected_start_time" value="{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}" class="time-input" required>
                            <span class="time-separator">〜</span>
                            <input type="time" name="corrected_end_time" value="{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}" class="time-input" required>
                        </div>
                    </div>

                    @php
                        $breaks = $attendance->breaks->take(2)->values();
                    @endphp

                    <div class="detail-row">
                        <div class="detail-label">休憩</div>
                        <div class="detail-value-group">
                            <input type="time" name="break_corrections[0][start_time]" value="{{ $breaks->get(0) && $breaks->get(0)->start_time ? \Carbon\Carbon::parse($breaks->get(0)->start_time)->format('H:i') : '' }}" class="time-input">
                            <span class="time-separator">〜</span>
                            <input type="time" name="break_corrections[0][end_time]" value="{{ $breaks->get(0) && $breaks->get(0)->end_time ? \Carbon\Carbon::parse($breaks->get(0)->end_time)->format('H:i') : '' }}" class="time-input">
                        </div>
                    </div>

                    <div class="detail-row">
                        <div class="detail-label">休憩2</div>
                        <div class="detail-value-group">
                            <input type="time" name="break_corrections[1][start_time]" value="{{ $breaks->get(1) && $breaks->get(1)->start_time ? \Carbon\Carbon::parse($breaks->get(1)->start_time)->format('H:i') : '' }}" class="time-input">
                            <span class="time-separator">〜</span>
                            <input type="time" name="break_corrections[1][end_time]" value="{{ $breaks->get(1) && $breaks->get(1)->end_time ? \Carbon\Carbon::parse($breaks->get(1)->end_time)->format('H:i') : '' }}" class="time-input">
                        </div>
                    </div>

                    <div class="detail-row detail-row--remarks">
                        <div class="detail-label">備考</div>
                        <div class="detail-value-full">
                            <textarea name="reason" class="remarks-textarea" placeholder="修正理由を入力してください" required>{{ old('reason') }}</textarea>
                            @error('reason')
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="button-wrapper">
                    <button type="submit" class="submit-button">修正</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
