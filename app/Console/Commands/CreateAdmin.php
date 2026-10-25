<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Secure super-administrator bootstrap. The password is never accepted as a
 * command-line argument: it is prompted (hidden) or read from
 * ADMIN_PASSWORD with --no-interaction.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {--email= : Adresse email} {--first-name=} {--last-name=} {--update-password : Autorise la modification d\'un compte existant}';

    protected $description = 'Crée (ou met à jour) un compte super administrateur de façon sécurisée';

    public function handle(): int
    {
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Adresse email invalide.');

            return self::FAILURE;
        }
        $existing = User::where('email', $email)->first();
        if ($existing && ! $this->option('update-password')) {
            $this->error('Ce compte existe déjà. Utilisez --update-password pour changer son mot de passe.');

            return self::FAILURE;
        }

        $password = $this->option('no-interaction') ? (string) env('ADMIN_PASSWORD', '') : (string) $this->secret('Mot de passe');
        if (! $this->option('no-interaction') && $password !== (string) $this->secret('Confirmation')) {
            $this->error('Les deux saisies ne correspondent pas.');

            return self::FAILURE;
        }
        $validator = Validator::make(['password' => $password], ['password' => ['required', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            $this->error('Mot de passe refusé : '.implode(' ', $validator->errors()->all()).($this->option('no-interaction') ? ' (variable ADMIN_PASSWORD)' : ''));

            return self::FAILURE;
        }

        $user = $existing ?? new User(['email' => $email, 'first_name' => (string) $this->option('first-name'), 'last_name' => (string) $this->option('last-name')]);
        $user->forceFill(['password' => $password, 'role' => Role::Superadmin, 'is_active' => true])->save();
        $this->info($existing ? "Mot de passe mis à jour pour {$email}." : "Super administrateur {$email} créé.");

        return self::SUCCESS;
    }
}
