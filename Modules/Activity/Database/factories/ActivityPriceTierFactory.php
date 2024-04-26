<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\PriceTier;

class ActivityPriceTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PriceTier::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'=> $this->faker->text(10),
            'price'=> $this->faker->randomFloat(2,100,200),
            'advertised_price'=> $this->faker->randomFloat(2,100,200)
        ];
    }
}

