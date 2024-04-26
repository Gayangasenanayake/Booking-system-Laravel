<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_name' => 'shopylk',
            'name' => 'shopylk',
            'email' => 'shopylk@gmail.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];
    }
}
