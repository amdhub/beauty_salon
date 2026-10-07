<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateSalonAdmin extends Command
{
    protected $signature = 'salon:admin {email} {--name=Administration} {--generate : Générer un mot de passe aléatoire}';

    protected $description = 'Créer un administrateur sans mot de passe par défaut';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $password = $this->option('generate') ? Str::password(20) : $this->secret('Mot de passe (12 caractères minimum, lettres et chiffres)');
        $data = ['email' => $email, 'name' => $this->option('name'), 'password' => $password];
        $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'name' => 'required|string|max:150', 'password' => ['required', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $role = Role::where('slug', 'administrator')->first();
        if (! $role) {
            $this->error('Exécutez d’abord php artisan db:seed.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($data, $role): void {
            $user = User::create($data);
            $user->roles()->attach($role);
        });
        $this->info('Administrateur créé : '.$email);
        if ($this->option('generate')) {
            $this->line('Mot de passe initial : '.$password);
        }

        return self::SUCCESS;
    }
}
