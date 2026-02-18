@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="admin-container">
    <h1 class="page-title">スタッフ一覧</h1>

    <div class="staff-table-wrapper">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
            </thead>
            <tbody>
                @foreach($staff as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <a href="{{ route('admin.staff.attendance', $user->id) }}" class="detail-link">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
