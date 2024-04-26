<?php

namespace Modules\Booking\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookingParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Booking\Entities\BookingParticipant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            "name"=>$this->faker->name,
            "email"=>$this->faker->email,
            "age"=>$this->faker->numberBetween(20,80),
            "dietary_requirements"=>$this->faker->randomElement(["Non-vegetarian","Vegetarian"]),
            "weight"=>$this->faker->numberBetween(30,100),
            "height"=>$this->faker->numberBetween(140,200),
            "health_issues"=>$this->faker->text(10),
            "other"=>$this->faker->text(10)
        ];
    }
}

