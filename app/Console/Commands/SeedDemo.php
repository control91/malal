<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature = 'app:seed-demo {--with-users : Crée un compte de démonstration par rôle (mots de passe aléatoires affichés)}';

    protected $description = "Crée des données d'EXEMPLE pour le développement (jamais en production)";

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('app:seed-demo est réservé au développement.');

            return self::FAILURE;
        }
        $seeder = new DemoSeeder((bool) $this->option('with-users'));
        $seeder->setContainer(app())->setCommand($this)->__invoke();
        foreach ($seeder->createdUsers as $user) {
            $this->line(sprintf('  %-22s %-24s mot de passe : %s', $user['role'], $user['email'], $user['password']));
        }
        $this->info("Données d'exemple créées.");

        return self::SUCCESS;
    }
}
