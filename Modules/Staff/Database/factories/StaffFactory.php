<?php

namespace Modules\Staff\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Staff\Entities\StaffMember;

class StaffFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StaffMember::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'title' => $this->faker->jobTitle(),
            'experience' => $this->faker->time(),
            'profile_data'=> $this->faker->address(),
            'status'=> $this->faker->domainWord(),
            'email'=> $this->faker->email(),
        ];
    }
}

