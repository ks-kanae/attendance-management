@extends('layouts.admin')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-attendance-detail.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">勤怠詳細</h1>

    {{-- ★ 承認待ちの場合はフォームを出さない --}}
    @if($hasPendingCorrection)
        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $attendance->user->name }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value">
                    {{ $attendance->date->format('Y年') }}
                    {{ $attendance->date->format('n月j日') }}
                </div>
            </div>

            {{-- 出勤・退勤（表示のみ） --}}
            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-value-group">
                    <span class="time-display">
                        {{ optional($attendance->start_time)->format('H:i') }}
                    </span>
                    <span class="time-separator">〜</span>
                    <span class="time-display">
                        {{ optional($attendance->end_time)->format('H:i') }}
                    </span>
                </div>
            </div>

            {{-- 休憩（表示のみ） --}}
            @foreach($attendance->breaks as $index => $break)
            <div class="detail-row">
                <div class="detail-label">休憩{{ $index == 0 ? '' : $index+1 }}</div>
                <div class="detail-value-group">
                    <span class="time-display">
                        {{ optional($break->start_time)->format('H:i') }}
                    </span>
                    <span class="time-separator">〜</span>
                    <span class="time-display">
                        {{ optional($break->end_time)->format('H:i') }}
                    </span>
                </div>
            </div>
            @endforeach

            {{-- 備考（表示のみ） --}}
            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value">
                    {{ optional($attendance->latestCorrection)->reason ?? $attendance->reason }}
                </div>
            </div>
        </div>

        {{-- ★ メッセージ表示 --}}
        <div class="pending-message">
            ＊承認待ちのため修正はできません。
        </div>

    @else
        {{-- ★ 承認待ちでない場合はフォーム表示 --}}
        <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST" class="detail-form">
            @csrf

            <div class="detail-card">

                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value">{{ $attendance->user->name }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">日付</div>
                    <div class="detail-value">
                        {{ $attendance->date->format('Y年') }}
                        {{ $attendance->date->format('n月j日') }}
                    </div>
                </div>

                {{-- 出勤・退勤（編集可） --}}
                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-value-wrapper">
                        <div class="detail-value-group">
                            <input type="time"
                                name="start_time"
                                value="{{ old('start_time', optional($attendance->display_start_time)->format('H:i')) }}"
                                class="time-input"
                                required>

                            <span class="time-separator">〜</span>

                            <input type="time"
                                name="end_time"
                                value="{{ old('end_time', optional($attendance->display_end_time)->format('H:i')) }}"
                                class="time-input"
                                required>
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

                {{-- 休憩（編集可） --}}
                @php
                    $breaks = $attendance->breaks->values();
                    $max = $breaks->count() + 1;
                @endphp

                @for($i = 0; $i < $max; $i++)
                    @php
                        $break = $breaks->get($i);
                    @endphp

                    <div class="detail-row">
                        <div class="detail-label">休憩{{ $i == 0 ? '' : $i+1 }}</div>
                        <div class="detail-value-wrapper">
                            <div class="detail-value-group time">
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
                                @error("breaks.$i.start_time")
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                                @error("breaks.$i.end_time")
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endfor

                {{-- 備考（編集可） --}}
                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value-wrapper">
                        <div class="detail-value-group time">
                            <textarea name="reason" class="remarks-textarea">{{ old('reason', $attendance->display_reason) }}</textarea>
                        </div>
                        <div class="error-area">
                            @error('reason')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-wrapper">
                <button type="submit" class="submit-button">修正</button>
            </div>

        </form>

    @endif

</div>
@endsection
