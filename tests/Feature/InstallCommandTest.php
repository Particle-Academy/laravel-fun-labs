<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Install Command Tests
|--------------------------------------------------------------------------
|
| Tests for the lfl:install artisan command.
| Covers config publishing, migrations, UI scaffolding, and all flags.
|
| Note: The command detects unit tests and runs non-interactively,
| using default values for all prompts automatically.
|
*/

describe('InstallCommand', function () {

    beforeEach(function () {
        // Ensure clean state before each test
        if (File::exists(config_path('lfl.php'))) {
            File::delete(config_path('lfl.php'));
        }
    });

    afterEach(function () {
        // Clean up after each test
        if (File::exists(config_path('lfl.php'))) {
            File::delete(config_path('lfl.php'));
        }
    });

    it('is registered as an artisan command', function () {
        $this->artisan('list')
            ->expectsOutputToContain('lfl:install')
            ->assertExitCode(0);
    });

    it('has correct signature with options', function () {
        $command = $this->app->make(\LaravelFunLab\Console\Commands\InstallCommand::class);

        expect($command->getName())->toBe('lfl:install');
    });

    it('publishes config file with default prefix', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->assertExitCode(0);

        expect(File::exists(config_path('lfl.php')))->toBeTrue();

        $configContent = File::get(config_path('lfl.php'));
        expect($configContent)->toContain("'lfl_'");
    });

    it('can customize table prefix via option', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true, '--prefix' => 'custom_'])
            ->assertExitCode(0);

        $configContent = File::get(config_path('lfl.php'));
        expect($configContent)->toContain("'custom_'");
    });

    it('overwrites config when force flag is provided', function () {
        // Create a dummy config file
        File::put(config_path('lfl.php'), '<?php return [];');

        $this->artisan('lfl:install', ['--force' => true, '--skip-migrations' => true])
            ->assertExitCode(0);

        $configContent = File::get(config_path('lfl.php'));
        expect($configContent)->toContain('table_prefix');
    });

    it('skips migrations when skip-migrations flag is provided', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->assertExitCode(0);

        expect(File::exists(config_path('lfl.php')))->toBeTrue();
    });

    it('displays success message after installation', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->expectsOutputToContain('Laravel Fun Lab installed')
            ->assertExitCode(0);
    });

    it('shows quick start guide in success message', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->expectsOutputToContain('Quick Start')
            ->expectsOutputToContain('Awardable')
            ->assertExitCode(0);
    });

    it('suggests ui flag when installed without ui', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->expectsOutputToContain('lfl:install --ui')
            ->assertExitCode(0);
    });

    it('includes config path in output', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->expectsOutputToContain('config/lfl.php')
            ->assertExitCode(0);
    });

    it('includes facade usage in quick start', function () {
        $this->artisan('lfl:install', ['--skip-migrations' => true])
            ->expectsOutputToContain('LaravelFunLab\Facades\LFL')
            ->assertExitCode(0);
    });

});

describe('InstallCommand UI flag', function () {

    beforeEach(function () {
        // Ensure clean state
        if (File::exists(config_path('lfl.php'))) {
            File::delete(config_path('lfl.php'));
        }
        if (File::isDirectory(resource_path('views/vendor/lfl'))) {
            File::deleteDirectory(resource_path('views/vendor/lfl'));
        }
    });

    afterEach(function () {
        // Clean up
        if (File::exists(config_path('lfl.php'))) {
            File::delete(config_path('lfl.php'));
        }
        if (File::isDirectory(resource_path('views/vendor/lfl'))) {
            File::deleteDirectory(resource_path('views/vendor/lfl'));
        }
    });

    it('installs UI components when ui flag is provided', function () {
        $this->artisan('lfl:install', ['--ui' => true, '--skip-migrations' => true])
            ->expectsOutputToContain('UI components installed')
            ->assertExitCode(0);
    });

    it('does not show ui tip when ui flag is provided', function () {
        $this->artisan('lfl:install', ['--ui' => true, '--skip-migrations' => true])
            ->doesntExpectOutputToContain('Tip:')
            ->assertExitCode(0);
    });

});
