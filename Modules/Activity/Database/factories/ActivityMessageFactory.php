<?php

namespace Modules\Activity\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Activity\Entities\Message;

class ActivityMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'subject'=> $this->faker->text(50),
            'body'=> $this->faker->text(100),
//            'attchement'=> $this->faker->url.'.pdf',
            'reply_email'=> $this->faker->email(),
            'from'=> $this->faker->email(),
            'to'=> $this->faker->email(),
            'days'=> $this->faker->numberBetween(1,3),
            'after_or_before'=> $this->faker->randomElement(['before','after'])
        ];
    }
}

