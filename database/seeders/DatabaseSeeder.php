<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $employers = [
            ['email' => 'employer@example.com', 'name' => 'Demo Employer'],
            ['email' => 'employer2@example.com', 'name' => 'Nadia Employer'],
            ['email' => 'employer3@example.com', 'name' => 'Farid Employer'],
        ];

        foreach ($employers as $employer) {
            User::updateOrCreate(
                ['email' => $employer['email']],
                [
                    'name' => $employer['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'employer',
                ]
            );
        }

        $candidates = [
            ['email' => 'candidate@example.com', 'name' => 'Demo Candidate'],
            ['email' => 'candidate2@example.com', 'name' => 'Aisyah Candidate'],
            ['email' => 'candidate3@example.com', 'name' => 'Wei Candidate'],
            ['email' => 'candidate4@example.com', 'name' => 'Ravi Candidate'],
        ];

        foreach ($candidates as $candidate) {
            User::updateOrCreate(
                ['email' => $candidate['email']],
                [
                    'name' => $candidate['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'candidate',
                ]
            );
        }
    }
}
