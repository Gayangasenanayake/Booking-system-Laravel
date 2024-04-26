<?php

namespace Modules\Customer\Database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'name' => $this->faker->name(),
            'password' => 'password',
//            'password_confirmation' => 'password',
            'email' => $this->faker->email(),
            'street' => $this->faker->streetName(),
            'city' => $this->faker->city(),
            'province' => $this->faker->country(),
            'mobile' => '0778337399',
            'age' => $this->faker->numberBetween(20, 80),
            'dietary_request' => $this->faker->numberBetween(1, 2),
        ];
    }
}

