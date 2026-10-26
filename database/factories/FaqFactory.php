<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pertanyaan' => rtrim(fake()->sentence(8), '.').'?',
            'jawaban' => fake()->paragraphs(2, true),
        ];
    }
}
