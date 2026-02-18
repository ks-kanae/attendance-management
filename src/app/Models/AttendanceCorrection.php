<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'corrected_start_time',
        'corrected_end_time',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'corrected_start_time' => 'datetime:H:i',
        'corrected_end_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function breakCorrections()
    {
        return $this->hasMany(BreakCorrection::class);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}
