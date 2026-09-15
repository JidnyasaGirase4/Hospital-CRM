<?php

namespace Database\Factories;

use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsuranceCompany>
 */
class InsuranceCompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Insurance',
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('##########'),
            'email' => fake()->unique()->companyEmail(),
            'is_active' => true,
        ];
    }
}
