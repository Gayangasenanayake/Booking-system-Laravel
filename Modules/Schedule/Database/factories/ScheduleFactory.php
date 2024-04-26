<?php

namespace Modules\Schedule\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Schedule\Entities\Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start_time = $this->faker->dateTimeBetween('now','+3 hours');
        $end_time = (clone $start_time)->modify('+3 hours');
        return [
            'date'=> $this->faker->dateTimeBetween(now(),now()->addDays(30))->format('y-m-d'),
            'start_time'=> $start_time->format('h:i'),
            'end_time'=> $end_time->format('h:i'),
            'allocated_slots'=> $this->faker->numberBetween(1,50),
            'min_number_of_places'=> $this->faker->numberBetween(1,5),
            'max_number_of_places'=> $this->faker->numberBetween(5,10),
        ];
    }
}

