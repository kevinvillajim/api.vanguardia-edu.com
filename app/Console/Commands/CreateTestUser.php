<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'user:create-test';
    protected $description = 'Create a test user for login testing';

    public function handle()
    {
        // Delete existing test user
        User::where('email', 'test@test.com')->delete();

        // Create new test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'ci' => '12345678',
            'role' => 1, // Admin role
            'active' => 1,
            'email_verified_at' => now(),
        ]);

        $this->info('Test user created successfully:');
        $this->info('Email: test@test.com');
        $this->info('Password: password');
        $this->info('Role: Admin (1)');

        return 0;
    }
}