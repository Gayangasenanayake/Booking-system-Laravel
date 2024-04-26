<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\ActivityPricingInfo;

class ActivityPriceInfoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityPricingInfo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'base_price' => $this->faker->randomFloat(2,1,100),
            'advertised_price' => $this->faker->randomFloat(2,1,100)
        ];
    }
}

