<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_id',
        'break_number',
        'corrected_start_time',
        'corrected_end_time',
    ];

    protected $casts = [
        'corrected_start_time' => 'datetime:H:i',
        'corrected_end_time' => 'datetime:H:i',
    ];

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
