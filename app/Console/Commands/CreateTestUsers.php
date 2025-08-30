<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test users for VanguardIA MVP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@vanguardia.com',
                'password' => Hash::make('admin123'),
                'ci' => '12345678',
                'role' => 1, // Admin
                'active' => true,
                'email_verified_at' => now()
            ],
            [
                'name' => 'Teacher User',
                'email' => 'teacher@vanguardia.com', 
                'password' => Hash::make('teacher123'),
                'ci' => '87654321',
                'role' => 3, // Teacher
                'active' => true,
                'email_verified_at' => now()
            ],
            [
                'name' => 'Student User',
                'email' => 'student@vanguardia.com',
                'password' => Hash::make('student123'),
                'ci' => '11111111',
                'role' => 2, // Student
                'active' => true,
                'email_verified_at' => now()
            ]
        ];

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])->first();
            if (!$user) {
                User::create($userData);
                $this->info("Created user: {$userData['email']}");
            } else {
                $this->info("User already exists: {$userData['email']}");
            }
        }

        $this->info('Test users created successfully!');
        $this->line('');
        $this->line('Login credentials:');
        $this->line('Admin: admin@vanguardia.com / admin123');
        $this->line('Teacher: teacher@vanguardia.com / teacher123');
        $this->line('Student: student@vanguardia.com / student123');
    }
}
