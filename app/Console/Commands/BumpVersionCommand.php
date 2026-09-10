<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BumpVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bump-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increments the application patch version in the .env file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $configPath = config_path('app.php');

        if (!file_exists($configPath)) {
            $this->error('config/app.php não encontrado.');
            return Command::FAILURE;
        }

        $configContent = file_get_contents($configPath);
        
        // Tenta capturar a versão de fallback atual no config/app.php
        if (preg_match('/\'version\'\s*=>\s*env\(\'APP_VERSION\',\s*\'([^\']+)\'\)/', $configContent, $matches)) {
            $currentVersion = $matches[1];
        } else {
            $this->error('Não foi possível encontrar a declaração da versão no config/app.php');
            return Command::FAILURE;
        }

        $parts = explode('.', $currentVersion);
        if (count($parts) === 3) {
            $parts[2] = (int)$parts[2] + 1;
            $newVersion = implode('.', $parts);
        } else {
            $this->error('O formato da versão é inválido. Esperado (ex: 1.0.0)');
            return Command::FAILURE;
        }

        // Substitui a versão no arquivo
        $newConfigContent = preg_replace(
            '/\'version\'\s*=>\s*env\(\'APP_VERSION\',\s*\'([^\']+)\'\)/',
            '\'version\' => env(\'APP_VERSION\', \'' . $newVersion . '\')',
            $configContent
        );

        file_put_contents($configPath, $newConfigContent);

        $this->info("Version bumped successfully from {$currentVersion} to {$newVersion} (in config/app.php)");
        
        return Command::SUCCESS;
    }
}
