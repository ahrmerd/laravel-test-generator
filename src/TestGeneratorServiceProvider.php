<?php

namespace Ahrmerd\TestGenerator;

use Ahrmerd\TestGenerator\Commands\GenerateTestsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TestGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {

        $package
            ->name('TestGenerator')
            ->hasConfigFile()
            // ->hasViews()
            // ->hasMigration('create_skeleton_table')
            ->hasCommand(GenerateTestsCommand::class);
    }
}
