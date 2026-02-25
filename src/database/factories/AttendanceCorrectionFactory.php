<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;

class AttendanceCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(),
            'corrected_start_time' => '09:00',
            'corrected_end_time' => '18:00',
            'reason' => 'テスト修正理由',
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
