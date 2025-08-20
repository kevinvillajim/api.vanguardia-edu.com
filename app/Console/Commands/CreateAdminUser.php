<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--email=admin@vanguardia.com : Admin email}
                            {--password=admin123 : Admin password}
                            {--name=Admin : Admin name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        $name = $this->option('name');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists!");
            return 1;
        }

        // Create admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 1, // Admin role
            'active' => 1,
            'ci' => '12345678',
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user created successfully!");
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");
        $this->line("Role: Admin (1)");

        return 0;
    }
}