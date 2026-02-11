<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = Attendance::class;

    public function definition()
    {
        $startHour = $this->faker->numberBetween(8, 10);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);

        $endHour = $this->faker->numberBetween(17, 19);
        $endMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $endTime = sprintf('%02d:%02d:00', $endHour, $endMinute);

        return [
            'user_id' => User::factory(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
