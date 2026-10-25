<?php

namespace App\Console\Commands;

use Database\Seeders\SiteContentSeeder;
use Illuminate\Console\Command;

class SeedSite extends Command
{
    protected $signature = 'app:seed-site {--force : Réécrit les contenus existants créés par cette commande}';

    protected $description = 'Crée le contenu institutionnel de base (idempotent, utilisable en production)';

    public function handle(): int
    {
        $seeder = new SiteContentSeeder;
        $seeder->force = (bool) $this->option('force');
        $seeder->setContainer(app())->setCommand($this)->__invoke();
        $this->info('Contenu institutionnel prêt.');

        return self::SUCCESS;
    }
}
