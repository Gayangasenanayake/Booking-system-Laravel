<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\Prerequisites;

class PrerequisiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Prerequisites::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'field_type'=> $this->faker->jobTitle(),
            'title'=> $this->faker->title(),
            'description'=> $this->faker->text(),
        ];
    }
}

