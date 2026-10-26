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
            'answer_type' => Faq::TYPE_TEXT,
            'answer_image' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'jawaban' => null,
            'answer_type' => Faq::TYPE_IMAGE,
            'answer_image' => Faq::IMAGE_DIR.'/'.fake()->uuid().'.jpg',
        ]);
    }

    public function both(): static
    {
        return $this->state(fn () => [
            'answer_type' => Faq::TYPE_BOTH,
            'answer_image' => Faq::IMAGE_DIR.'/'.fake()->uuid().'.jpg',
        ]);
    }
}
