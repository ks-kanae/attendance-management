@extends('layouts.admin')

@section('title', '勤怠一覧')


@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-attendance-list.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>

    <div class="date-selector">
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}" class="date-nav-button">
            ← 前日
        </a>
        <div class="current-date">
            <input type="date"
            value="{{ $date->format('Y-m-d') }}"
            onchange="location.href='{{ route('admin.attendance.list') }}?date=' + this.value"
            class="date-input">
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}" class="date-nav-button">
            翌日 →
        </a>
    </div>

    <div class="attendance-table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $data)
                <tr>
                    <td>{{ $data['user']->name }}</td>
                    @if($data['attendance'])
                        <td>{{ $data['attendance']->start_time ? \Carbon\Carbon::parse($data['attendance']->start_time)->format('H:i') : '' }}</td>
                        <td>{{ $data['attendance']->end_time ? \Carbon\Carbon::parse($data['attendance']->end_time)->format('H:i') : '' }}</td>
                        <td>{{ $data['total_break_time'] ?? '' }}</td>
                        <td>{{ $data['total_work_time'] ?? '' }}</td>
                        <td>
                            <a href="#" class="detail-link">詳細</a>
                        </td>
                    @else
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
