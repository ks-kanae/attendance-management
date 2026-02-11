<?php

namespace Database\Factories;

use App\Models\WorkBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = WorkBreak::class;

    public function definition()
    {
        $startHour = $this->faker->numberBetween(12, 13);
        $startMinute = $this->faker->randomElement([0, 15, 30]);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);

        $endHour = $startHour;
        $endMinute = $startMinute + $this->faker->numberBetween(30, 60);
        if ($endMinute >= 60) {
            $endHour++;
            $endMinute -= 60;
        }
        $endTime = sprintf('%02d:%02d:00', $endHour, $endMinute);

        return [
            'attendance_id' => Attendance::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
