<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    // Static counter to alternate likes
    protected static int $counter = 0;

    public function definition(): array
    {
                // Increment counter
        self::$counter++;

        // Alternate between <100k and >=100k likes
        $likes = self::$counter % 2 === 0
            ? $this->faker->numberBetween(100001, 500000) // Above 100k
            : $this->faker->numberBetween(1, 100000);      // Below and equal to 100k

        return [
            'username' => strtolower($this->faker->unique()->userName()),
            'likes' => $likes,
        ];
    }
}
