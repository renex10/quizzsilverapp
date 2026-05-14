<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'series_id' => Series::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'version' => $this->faker->year(),
            'type' => $this->faker->randomElement(['single_choice', 'multiple_choice', 'true_false', 'ordering', 'matching']),
            'json_schema' => json_encode([
                'exam' => [
                    'id' => $this->faker->uuid(),
                    'title' => $this->faker->sentence(4),
                    'version' => $this->faker->year(),
                    'passingScore' => $this->faker->numberBetween(60, 90),
                    'timeLimitMinutes' => $this->faker->numberBetween(30, 90),
                    'shuffleQuestions' => $this->faker->boolean(),
                ],
                'questions' => [
                    [
                        'id' => 'Q1',
                        'type' => 'single_choice',
                        'question' => $this->faker->sentence(),
                        'options' => [
                            ['id' => 'A', 'text' => $this->faker->word()],
                            ['id' => 'B', 'text' => $this->faker->word()],
                        ],
                        'correctAnswer' => 'A',
                        'category' => $this->faker->word(),
                        'difficulty' => $this->faker->randomElement(['baja', 'media', 'alta']),
                        'critical' => $this->faker->boolean(10),
                        'explanation' => $this->faker->paragraph(),
                    ],
                ],
            ]),
            'status' => $this->faker->randomElement(['draft', 'published']),
        ];
    }
}