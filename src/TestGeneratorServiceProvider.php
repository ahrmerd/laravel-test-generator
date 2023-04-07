<?php

namespace Ahrmerd\TestGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ahrmerd\TestGenerator\Commands\GenerateTestsCommand;

class TestGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {

        $package
            ->name('TestGenerator')
            // ->hasConfigFile()
            // ->hasViews()
            // ->hasMigration('create_skeleton_table')
            ->hasCommand(GenerateTestsCommand::class);
    }
}
