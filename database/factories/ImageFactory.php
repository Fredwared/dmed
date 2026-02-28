<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_filename' => fake()->word().'.jpg',
            'storage_path' => 'images/1/'.Str::random(64).'.webp',
            'mime_type' => 'image/webp',
            'file_size' => fake()->numberBetween(1000, 5_000_000),
            'file_hash' => hash('sha256', Str::random(40)),
            'status' => Image::STATUS_READY,
            'width' => fake()->numberBetween(100, 4000),
            'height' => fake()->numberBetween(100, 4000),
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => Image::STATUS_PENDING,
            'mime_type' => 'image/jpeg',
            'width' => null,
            'height' => null,
        ]);
    }
}
