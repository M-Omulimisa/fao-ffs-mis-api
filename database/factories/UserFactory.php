<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $data = [
            'name' => $this->faker->name(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        ];

        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'username')) {
                $data['username'] = $this->faker->unique()->userName();
            }
            if (Schema::hasColumn('users', 'email')) {
                $data['email'] = $this->faker->unique()->safeEmail();
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $data['email_verified_at'] = now();
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $data['remember_token'] = Str::random(10);
            }
        }

        return $data;
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            if (Schema::hasTable('users') && Schema::hasColumn('users', 'email_verified_at')) {
                return [
                    'email_verified_at' => null,
                ];
            }

            return [];
        });
    }
}
