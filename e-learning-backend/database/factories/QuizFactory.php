<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Lessons\Models\Lesson;
use Modules\Quiz\Models\Quiz;

class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'max_attempts' => 3,
            'time_limit' => null,
            'status' => 1,
        ];
    }
}
