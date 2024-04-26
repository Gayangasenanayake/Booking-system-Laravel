<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\Seo;

class ActivitySeoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Seo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'meta_title'=> $this->faker->title(),
            'meta_description'=> $this->faker->text(100),
        ];
    }
}

