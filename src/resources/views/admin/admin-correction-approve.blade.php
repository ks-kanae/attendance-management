@extends('layouts.admin')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-correction-approve.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">勤怠詳細</h1>

    <form method="POST" action="{{ route('admin.correction.approve', $correction->id) }}">
    @csrf
        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-label">名前</div>
                <div class="detail-value">{{ $correction->attendance->user->name }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">日付</div>
                <div class="detail-value">{{ $correction->attendance->date->format('Y年') }}　{{ $correction->attendance->date->format('n月j日') }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">出勤・退勤</div>
                <div class="detail-value-group">
                    <span class="time-display">{{ $correction->corrected_start_time ? \Carbon\Carbon::parse($correction->corrected_start_time)->format('H:i') : '' }}</span>
                    <span class="time-separator">〜</span>
                    <span class="time-display">{{ $correction->corrected_end_time ? \Carbon\Carbon::parse($correction->corrected_end_time)->format('H:i') : '' }}</span>
                </div>
            </div>

            @php
                $breaks = $correction->breakCorrections->sortBy('break_number')->values();
            @endphp

            {{-- 既存の休憩 --}}
            @foreach($breaks as $index => $break)
                <div class="detail-row">
                    <div class="detail-label">
                        休憩{{ $index === 0 ? '' : $index + 1 }}
                    </div>

                    <div class="detail-value-group">
                        <span class="time-display">
                            {{ $break->corrected_start_time ? \Carbon\Carbon::parse($break->corrected_start_time)->format('H:i') : '' }}
                        </span>

                        <span class="time-separator">〜</span>

                        <span class="time-display">
                            {{ $break->corrected_end_time ? \Carbon\Carbon::parse($break->corrected_end_time)->format('H:i') : '' }}
                        </span>
                    </div>
                </div>
            @endforeach

            {{-- ★追加用の空行（重要） --}}
            @php
                $emptyCount = max(0, 2 - $breaks->count());
            @endphp

            @for($i = 0; $i < $emptyCount; $i++)
            <div class="detail-row">
                <div class="detail-label">
                    休憩{{ $breaks->count() + $i === 0 ? '' : $breaks->count() + $i + 1 }}
                </div>
            </div>
            @endfor

            <div class="detail-row">
                <div class="detail-label">備考</div>
                <div class="detail-value">{{ $correction->reason }}</div>
            </div>
        </div>

        <div class="button-wrapper">
            <button type="submit"
                    class="approval-button {{ $correction->status === 'approved' ? 'approval-button--approved' : '' }}"
                    id="approvalButton"
                    data-correction-id="{{ $correction->id }}"
                    {{ $correction->status === 'approved' ? 'disabled' : '' }}>
                {{ $correction->status === 'approved' ? '承認済み' : '承認' }}
            </button>
        </div>
    </form>
</div>

@endsection
