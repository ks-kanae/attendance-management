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
        return $this->hasMany(WorkBreak::class)->orderBy('id');
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

    public function getDisplayBreaksAttribute()
    {
        $breaks = $this->breaks->values();
        $correction = $this->latestCorrection;

        if (!$correction) {
            return $breaks;
        }

        $breakCorrections = $correction->breakCorrections->keyBy('break_number');

        $max = max($breaks->count(), $breakCorrections->count());

        $results = collect();

        for ($i = 1; $i <= $max; $i++) {
            $break = $breaks->get($i - 1);
            $correction = $breakCorrections->get($i);

            $results->push([
                'start_time' => $correction->corrected_start_time ?? $break->start_time ?? null,
                'end_time'   => $correction->corrected_end_time ?? $break->end_time ?? null,
            ]);
        }

        return $results;
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time
            ? $this->start_time->format('H:i')
            : '';
    }

    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time
        ? $this->end_time->format('H:i')
        : '';
    }

    public function getDisplayStartTimeAttribute()
    {
        return optional($this->approvedCorrection)->corrected_start_time
        ?? $this->start_time;
    }

    public function getDisplayEndTimeAttribute()
    {
        return optional($this->approvedCorrection)->corrected_end_time
        ?? $this->end_time;
    }

    public function getDisplayReasonAttribute()
    {
        return optional($this->approvedCorrection)->reason
        ?? $this->reason;
    }

    public function getApprovedCorrectionAttribute()
    {
        return $this->corrections()
        ->where('status', 'approved')
        ->latest()
        ->first();
    }
}
