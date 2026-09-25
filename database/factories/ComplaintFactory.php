<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'category' => 'road_damage',
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'urgency' => 'Medium',
            'status' => ComplaintStatus::Submitted->value,
            'is_public' => true,
            'suggested_priority' => 'elevated',
            'confirmed_priority' => 'elevated',
            'review_status' => 'verified',
            'reviewed_at' => now(),
            'address_text' => 'Brgy. Lag-on, Daet, Camarines Norte',
            'latitude' => 14.1122,
            'longitude' => 122.9553,
        ];
    }
}
