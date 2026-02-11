@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-list-container">
    <div class="attendance-list-content">
        <h1 class="page-title">勤怠一覧</h1>

        {{-- 月選択 --}}
        <div class="month-selector">
            <a href="{{ route('attendance.list', ['year' => $targetDate->copy()->subMonth()->year, 'month' => $targetDate->copy()->subMonth()->month]) }}" class="month-nav-button">
                ＜ 前月
            </a>
            <div class="current-month">
                <span class="month-display">{{ $targetDate->format('Y/m') }}</span>
            </div>
            <a href="{{ route('attendance.list', ['year' => $targetDate->copy()->addMonth()->year, 'month' => $targetDate->copy()->addMonth()->month]) }}" class="month-nav-button">
                翌月 ＞
            </a>
        </div>

        {{-- 勤怠テーブル --}}
        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dates as $dateData)
                    <tr>
                        <td class="date-cell">
                            {{ $dateData['date']->format('m/d') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$dateData['date']->dayOfWeek] }})
                        </td>
                        @if($dateData['attendance'])
                            <td>{{ $dateData['attendance']->start_time ? \Carbon\Carbon::parse($dateData['attendance']->start_time)->format('H:i') : '' }}</td>
                            <td>{{ $dateData['attendance']->end_time ? \Carbon\Carbon::parse($dateData['attendance']->end_time)->format('H:i') : '' }}</td>
                            <td>{{ $dateData['total_break_time'] ?? '' }}</td>
                            <td>{{ $dateData['total_work_time'] ?? '' }}</td>
                            <td>
                                <a href="{{ route('attendance.detail', $dateData['attendance']->id) }}" class="detail-link">
                                    詳細
                                </a>
                            </td>
                        @else
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <span class="detail-link detail-link--disabled">詳細</span>
                            </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
