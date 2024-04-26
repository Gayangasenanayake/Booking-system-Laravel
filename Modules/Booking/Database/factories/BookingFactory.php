<?php

namespace Modules\Booking\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Database\factories\ActivityFactory;
use Modules\Activity\Database\factories\ActivityPriceTierFactory;
use Modules\Activity\Database\factories\ActivityScheduleGroupFactory;
use Modules\Schedule\Database\factories\ScheduleFactory;

class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Booking\Entities\Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $activity = ActivityFactory::new()->connection('tenant')->create();
        $price_tier = ActivityPriceTierFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id]);
        $schedule = ScheduleFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        $schedule_group = ActivityScheduleGroupFactory::new()->connection('tenant')->create(['activity_id'=>$activity->id,'price_tier_id'=>$price_tier->id]);
        return [
            'booking_items'=>[
                [
                    "type"=>"schedule",
                    "id"=>$schedule->id,
                    "number_of_slots"=> 3
                ],
                [
                    "type"=>"schedule_group",
                    "id"=>$schedule_group->id,
                    "number_of_slots"=> 4
                ]
            ],
            'booking_participants'=>[
                [
                    "name"=>$this->faker->name,
                    "email"=>$this->faker->email,
                    "age"=>$this->faker->numberBetween(20,80),
                    "dietary_requirements"=>$this->faker->randomElement(["Non-vegetarian","Vegetarian"]),
                    "weight"=>$this->faker->numberBetween(30,100),
                    "height"=>$this->faker->numberBetween(140,200),
                    "health_issues"=>$this->faker->text(10),
                    "other"=>$this->faker->text(10)
                ],
                [
                    "name"=>$this->faker->name,
                    "email"=>$this->faker->email,
                    "age"=>$this->faker->numberBetween(20,80),
                    "dietary_requirements"=>$this->faker->randomElement(["Non-vegetarian","Vegetarian"]),
                    "weight"=>$this->faker->numberBetween(30,100),
                    "height"=>$this->faker->numberBetween(140,200),
                    "health_issues"=>$this->faker->text(10),
                    "other"=>$this->faker->text(10)
                ]
            ]
        ];
    }
}

