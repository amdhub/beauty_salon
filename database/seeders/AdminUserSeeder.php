<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('salon.admin_email');
        $password = config('salon.admin_password');
        if (! $email || ! $password) {
            $this->command?->warn('Administrateur non créé : renseignez SALON_ADMIN_EMAIL et SALON_ADMIN_PASSWORD dans .env.');

            return;
        }
        Validator::make(['email' => $email, 'password' => $password], ['email' => 'required|email', 'password' => ['required', Password::min(12)->letters()->numbers()]])->validate();
        $user = User::firstOrCreate(['email' => $email], ['name' => 'Administration', 'password' => $password]);
        if ($user->wasRecentlyCreated) {
            $user->roles()->attach(Role::where('slug', 'administrator')->firstOrFail());
        }
    }
}
