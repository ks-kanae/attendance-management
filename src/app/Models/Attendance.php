<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(WorkBreak::class);
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function latestCorrection()
    {
        return $this->hasOne(AttendanceCorrection::class)->latest();
    }

    public function getBreakTimeAttribute()
    {
    $totalMinutes = 0;

    foreach ($this->breaks as $break) {
        if ($break->start_time && $break->end_time) {
            $start = Carbon::parse($break->start_time);
            $end = Carbon::parse($break->end_time);
            $totalMinutes += $start->diffInMinutes($end);
        }
    }

    $hours = floor($totalMinutes / 60);
    $minutes = $totalMinutes % 60;

    return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getWorkTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        $workMinutes = $start->diffInMinutes($end);

        $breakMinutes = $this->breaks->sum(function ($break) {
            if ($break->start_time && $break->end_time) {
                return Carbon::parse($break->start_time)
                    ->diffInMinutes(Carbon::parse($break->end_time));
            }
            return 0;
        });

        $totalMinutes = $workMinutes - $breakMinutes;

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getStatusAttribute()
    {
        if (!$this->start_time) {
            return 'not_working';
        }

        if ($this->end_time) {
            return 'finished';
        }

        $lastBreak = $this->breaks->sortByDesc('start_time')->first();

        if ($lastBreak && !$lastBreak->end_time) {
            return 'on_break';
        }

        return 'working';
    }
}
