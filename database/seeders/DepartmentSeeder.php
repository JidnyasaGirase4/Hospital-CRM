<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'General Medicine', 'code' => 'GEN-MED'],
            ['name' => 'Cardiology', 'code' => 'CARDIO'],
            ['name' => 'Orthopedics', 'code' => 'ORTHO'],
            ['name' => 'Pediatrics', 'code' => 'PEDS'],
            ['name' => 'Gynecology', 'code' => 'GYNAE'],
            ['name' => 'ENT', 'code' => 'ENT'],
            ['name' => 'Dermatology', 'code' => 'DERMA'],
            ['name' => 'Neurology', 'code' => 'NEURO'],
            ['name' => 'Emergency', 'code' => 'ER'],
            ['name' => 'Radiology', 'code' => 'RAD'],
            ['name' => 'Pathology / Laboratory', 'code' => 'LAB'],
            ['name' => 'Pharmacy', 'code' => 'PHARM'],
            ['name' => 'Surgery / OT', 'code' => 'OT'],
            ['name' => 'Administration', 'code' => 'ADMIN'],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
