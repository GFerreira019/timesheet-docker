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
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file not found.');
            return Command::FAILURE;
        }

        $envContent = file_get_contents($envPath);
        $currentVersion = env('APP_VERSION', '1.0.0');

        if (preg_match('/^APP_VERSION=(.*)$/m', $envContent, $matches)) {
            $currentVersion = trim($matches[1]);
            // Remove double quotes if present
            $currentVersion = trim($currentVersion, '"\'');
        }

        $parts = explode('.', $currentVersion);
        if (count($parts) === 3) {
            $parts[2] = (int)$parts[2] + 1;
            $newVersion = implode('.', $parts);
        } else {
            $this->error('APP_VERSION format is invalid. Expected semantic versioning (e.g. 1.0.0)');
            return Command::FAILURE;
        }

        if (preg_match('/^APP_VERSION=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_VERSION=.*$/m', 'APP_VERSION=' . $newVersion, $envContent);
        } else {
            $envContent .= "\nAPP_VERSION=" . $newVersion . "\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info("Version bumped successfully from {$currentVersion} to {$newVersion}");
        
        return Command::SUCCESS;
    }
}
