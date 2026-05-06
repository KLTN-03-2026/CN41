<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Quiz\Models\Quiz;
use Modules\Quiz\Models\QuizQuestion;

class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question' => $this->faker->sentence().'?',
            'option_a' => $this->faker->sentence(),
            'option_b' => $this->faker->sentence(),
            'option_c' => $this->faker->sentence(),
            'option_d' => $this->faker->sentence(),
            'correct_option' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'order' => 0,
        ];
    }
}
