<?php

namespace Modules\Product\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Entities\ProductPricingInfo;

class ProductPricingInfoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductPricingInfo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'base_price' => $this->faker->randomFloat(2,1,100),
            'advertised_price' => $this->faker->randomFloat(2,1,100)
        ];
    }
}

