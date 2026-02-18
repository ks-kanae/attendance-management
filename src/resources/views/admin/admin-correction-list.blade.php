@extends('layouts.admin')

@section('title', '申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-correction-list.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">申請一覧</h1>

    {{-- タブ --}}
    <div class="tab-wrapper">
        <a href="{{ route('admin.correction.list', ['tab' => 'pending']) }}"
            class="tab-button {{ $tab === 'pending' ? 'tab-button--active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.correction.list', ['tab' => 'approved']) }}"
            class="tab-button {{ $tab === 'approved' ? 'tab-button--active' : '' }}">
            承認済み
        </a>
    </div>

    {{-- 申請テーブル --}}
    <div class="correction-table-wrapper">
        @if($corrections->isEmpty())
            <div class="empty-message">
                @if($tab === 'pending')
                    承認待ちの申請はありません
                @else
                    承認済みの申請はありません
                @endif
            </div>
        @else
            <table class="correction-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($corrections as $correction)
                    <tr>
                        <td>
                            <span class="status-badge status-badge--{{ $correction->status }}">
                                @if($correction->status === 'pending')
                                    承認待ち
                                @elseif($correction->status === 'approved')
                                    承認済み
                                @elseif($correction->status === 'rejected')
                                    却下
                                @endif
                            </span>
                        </td>
                        <td>{{ $correction->user->name }}</td>
                        <td>{{ $correction->attendance->date->format('Y/m/d') }}</td>
                        <td class="reason-cell">{{ Str::limit($correction->reason, 20) }}</td>
                        <td>{{ $correction->created_at->format('Y/m/d') }}</td>
                        <td>
                            <a href="{{ route('admin.correction.show', $correction->id) }}" class="detail-link">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
