<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceCorrection;

class BreakCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_correction_id' => AttendanceCorrection::factory(),
            'break_number' => 1,
            'corrected_start_time' => '12:00',
            'corrected_end_time' => '13:00',
        ];
    }
}
