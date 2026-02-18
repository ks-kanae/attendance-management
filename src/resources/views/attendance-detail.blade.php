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
                    <div class="detail-value">
                        {{ $attendance->date->format('Y年') }}　
                        {{ $attendance->date->format('n月j日') }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-value-group">
                        <span class="time-display">
                            {{ $attendance->latestCorrection->corrected_start_time
                                ? \Carbon\Carbon::parse($attendance->latestCorrection->corrected_start_time)->format('H:i')
                                : \Carbon\Carbon::parse($attendance->start_time)->format('H:i') }}
                        </span>
                        <span class="time-separator">〜</span>
                        <span class="time-display">
                            {{ $attendance->latestCorrection->corrected_end_time
                                ? \Carbon\Carbon::parse($attendance->latestCorrection->corrected_end_time)->format('H:i')
                                : \Carbon\Carbon::parse($attendance->end_time)->format('H:i') }}
                        </span>
                    </div>
                </div>

                @php
                    $breaks = $attendance->breaks->values();
                    $breakCorrections = $attendance->latestCorrection->breakCorrections->keyBy('break_number');

                    $existingCount = max($breaks->count(), $breakCorrections->count());
                @endphp

                @for($i = 1; $i <= $existingCount; $i++)
                    @php
                        $break = $breaks->get($i - 1);
                        $correction = $breakCorrections->get($i);

                        $start = $correction->corrected_start_time ?? ($break->start_time ?? null);
                        $end   = $correction->corrected_end_time   ?? ($break->end_time ?? null);
                    @endphp

                    <div class="detail-row">
                        <div class="detail-label">休憩{{ $i > 1 ? $i : '' }}</div>
                        <div class="detail-value-group">
                            <span class="time-display">
                                {{ $start ? \Carbon\Carbon::parse($start)->format('H:i') : '' }}
                            </span>
                            <span class="time-separator">〜</span>
                            <span class="time-display">
                                {{ $end ? \Carbon\Carbon::parse($end)->format('H:i') : '' }}
                            </span>
                        </div>
                    </div>
                @endfor

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value">
                        {{ $attendance->latestCorrection->reason }}
                    </div>
                </div>
            </div>

            <div class="pending-message">
                ＊承認待ちのため修正はできません。
            </div>

        @else
            {{-- 修正可能フォーム --}}
            <form action="{{ route('attendance.correct', $attendance->id) }}" method="POST" class="detail-form" novalidate>
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

                    <div class="detail-row">
                        <div class="detail-label">出勤・退勤</div>
                        <div class="detail-value-group-wrapper">
                            <div class="detail-value-group">
                                <input type="time"
                                    name="corrected_start_time"
                                    value="{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}"
                                    class="time-input">

                                <span class="time-separator">〜</span>

                                <input type="time"
                                    name="corrected_end_time"
                                    value="{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}"
                                    class="time-input">
                            </div>
                            <div class="error-area">
                            @error('corrected_start_time')
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                            @error('corrected_end_time')
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                            </div>
                        </div>
                    </div>

                    @php
                        $breaks = $attendance->breaks->values();
                        $max = $breaks->count() + 1; // 常に空1つだけ
                    @endphp

                    @for($i = 0; $i < $max; $i++)
                        <div class="detail-row">
                            <div class="detail-label">休憩{{ $i > 0 ? $i+1 : '' }}</div>
                            <div class="detail-value-group-wrapper">
                                <div class="detail-value-group">
                                    <input type="time"
                                        name="break_corrections[{{ $i }}][start_time]"
                                        value="{{ optional($breaks->get($i))->start_time ? \Carbon\Carbon::parse($breaks->get($i)->start_time)->format('H:i') : '' }}"
                                        class="time-input">
                                    <span class="time-separator">〜</span>
                                    <input type="time"
                                        name="break_corrections[{{ $i }}][end_time]"
                                        value="{{ optional($breaks->get($i))->end_time ? \Carbon\Carbon::parse($breaks->get($i)->end_time)->format('H:i') : '' }}"
                                        class="time-input">
                                </div>
                                <div class="error-area">
                                    @error("break_corrections.$i.start_time")
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                    @error("break_corrections.$i.end_time")
                                    <p class="error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endfor

                    <div class="detail-row detail-row--remarks">
                        <div class="detail-label">備考</div>
                        <div class="detail-value-full">
                            <textarea name="reason"
                                class="remarks-textarea"
                                placeholder="修正理由を入力してください">{{ old('reason') }}</textarea>

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
