<?php

namespace Ahrmerd\TestGenerator\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Ahrmerd\TestGenerator\TestGeneratorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {

    }

    protected function getPackageProviders($app)
    {
        return [
            TestGeneratorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_skeleton_table.php.stub';
        $migration->up();
        */
    }
}
