@extends('layouts.admin')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-attendance-detail.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">勤怠詳細</h1>

    @if(session('success'))
    <div class="success-message">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="detail-form">
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
                <div class="detail-value-wrapper">
                    <div class="detail-value-group">
                        <input type="time" name="start_time" value="{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}" class="time-input" required>
                        <span class="time-separator">〜</span>
                        <input type="time" name="end_time" value="{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}" class="time-input" required>
                    </div>
                    <div class="error-area">
                    @error('start_time')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                    @error('end_time')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                    </div>
                </div>
            </div>

            @php
                $breaks = $attendance->breaks->values();
                $max = $breaks->count() + 1; // 既存 + 新規1行だけ
            @endphp

            @for($i = 0; $i < $max; $i++)
                @php
                    $break = $breaks->get($i);
                @endphp

                <div class="detail-row">
                    <div class="detail-label">休憩{{ $i == 0 ? '' : $i+1 }}</div>
                    <div class="detail-value-wrapper">
                        <div class="detail-value-group">
                            <input type="time"
                                name="breaks[{{ $i }}][start_time]"
                                value="{{ $break && $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('H:i') : '' }}"
                                class="time-input">

                            <span class="time-separator">〜</span>

                            <input type="time"
                                name="breaks[{{ $i }}][end_time]"
                                value="{{ $break && $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('H:i') : '' }}"
                                class="time-input">
                        </div>
                        <div class="error-area">
                            @error("breaks.{$i}.start_time")
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error("breaks.{$i}.end_time")
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endfor

            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value-textarea">
                    <textarea name="reason" class="remarks-textarea" placeholder="備考を入力してください">{{ old('reason', $attendance->reason) }}</textarea>
                    @error('reason')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="button-wrapper">
            <button type="submit" class="submit-button">修正</button>
        </div>
    </form>
</div>
@endsection
