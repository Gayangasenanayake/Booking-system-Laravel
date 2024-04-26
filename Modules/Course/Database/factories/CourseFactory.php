<?php

namespace Modules\Course\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Database\factories\ActivityFactory;

class CourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Course\Entities\Course::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $min_price = $this->faker->randomFloat(2,1000,5000);
        $start_date = $this->faker->dateTimeBetween(now()->addDays(2),now()->addDays(10));
        $end_date = clone $start_date;
        $activity = ActivityFactory::new()->connection('tenant')->create();
        return [
            'name'=> $this->faker->text(20),
            'frequency'=> $this->faker->text(10),
            'price'=> $min_price,
            'original_price'=> $min_price + $this->faker->randomFloat(2,1000,2000),
            'summery'=> $this->faker->text(100),
            'start_date'=> $start_date->format('y-m-d'),
            'end_date'=> $end_date->modify('+3 months')->format('y-m-d'),
            'activity_id'=>$activity->id,
        ];
    }
}

