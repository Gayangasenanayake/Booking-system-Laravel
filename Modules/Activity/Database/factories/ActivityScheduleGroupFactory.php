<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\ScheduleGroup;

class ActivityScheduleGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduleGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $from_date = $this->faker->dateTimeBetween(now()->addDays(7),now()->addDays(14));
        $to_date = (clone $from_date)->modify('+6 months');
        $start_time = $this->faker->dateTimeBetween('now','+3 hours');
        $end_time = (clone $start_time)->modify('+3 hours');

        return [
            'from_date'=> $from_date->format('y-m-d'),
            'to_date'=> $to_date->format('y-m-d'),
            'day'=> "['Monday','Tuesday','Friday']",
            'start_time'=> $start_time->format('h:i'),
            'end_time'=> $end_time->format('h:i'),
            'allocated_slots'=> $this->faker->numberBetween(1,50),
            'min_number_of_places'=> $this->faker->numberBetween(1,5),
            'max_number_of_places'=> $this->faker->numberBetween(5,10)
        ];
    }
}

