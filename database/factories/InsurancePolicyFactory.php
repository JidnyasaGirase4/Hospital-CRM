<?php

namespace Database\Factories;

use App\Models\InsuranceCompany;
use App\Models\InsurancePolicy;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsurancePolicy>
 */
class InsurancePolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'insurance_company_id' => InsuranceCompany::factory(),
            'policy_number' => 'POL'.fake()->unique()->numerify('######'),
            'valid_from' => now()->subYear(),
            'valid_till' => now()->addYear(),
            'coverage_amount' => 500000,
        ];
    }
}
