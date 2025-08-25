<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for authentication testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->info("Test user created/updated:");
        $this->info("Email: admin@test.com");
        $this->info("Password: password");
        
        return 0;
    }
}
